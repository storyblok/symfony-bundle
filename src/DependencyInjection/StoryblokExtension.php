<?php

declare(strict_types=1);

/**
 * This file is part of storyblok/symfony-bundle.
 *
 * (c) Storyblok GmbH <info@storyblok.com>
 * in cooperation with SensioLabs Deutschland <info@sensiolabs.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Storyblok\Bundle\DependencyInjection;

use Storyblok\Api\AssetsApi;
use Storyblok\Api\AssetsApiInterface;
use Storyblok\Api\Resolver\ResolverInterface;
use Storyblok\Api\Resolver\StoryResolver;
use Storyblok\Api\StoriesApi;
use Storyblok\Api\StoriesApiInterface;
use Storyblok\Api\StoriesResolvedApi;
use Storyblok\Api\StoryblokClient;
use Storyblok\Api\StoryblokClientInterface;
use Storyblok\Bundle\Block\Attribute\AsBlock;
use Storyblok\Bundle\Block\BlockRegistry;
use Storyblok\Bundle\Cdn\CdnUrlGenerator;
use Storyblok\Bundle\Cdn\CdnUrlGeneratorInterface;
use Storyblok\Bundle\Cdn\Download\AssetDownloader;
use Storyblok\Bundle\Cdn\Download\FileDownloaderInterface;
use Storyblok\Bundle\Cdn\Storage\CdnFilesystemStorage;
use Storyblok\Bundle\Cdn\Storage\CdnStorageInterface;
use Storyblok\Bundle\Cdn\Storage\TraceableCdnStorage;
use Storyblok\Bundle\Command\CdnCleanupCommand;
use Storyblok\Bundle\ContentType\Attribute\AsContentTypeController;
use Storyblok\Bundle\ContentType\ContentTypeControllerRegistry;
use Storyblok\Bundle\ContentType\Listener\GlobalCachingListener;
use Storyblok\Bundle\ContentType\Listener\StoryNotFoundExceptionListener;
use Storyblok\Bundle\Controller\CdnController;
use Storyblok\Bundle\DataCollector\CdnCollector;
use Storyblok\Bundle\DataCollector\StoryblokCollector;
use Storyblok\Bundle\Listener\UpdateProfilerListener;
use Storyblok\Bundle\Maker\MakeStoryblokBlock;
use Storyblok\Bundle\Maker\SchemaMapper;
use Storyblok\Bundle\Maker\Storyblok\ComponentProviderInterface;
use Storyblok\Bundle\Maker\Storyblok\ManagementApiComponentProvider;
use Storyblok\Bundle\Twig\CdnExtension;
use Storyblok\Bundle\Webhook\Handler\WebhookHandlerInterface;
use Storyblok\ManagementApi\Endpoints\ComponentApi;
use Storyblok\ManagementApi\ManagementApiClient;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpClient\TraceableHttpClient;
use function Symfony\Component\String\u;

final class StoryblokExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__).'/../config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->registerForAutoconfiguration(WebhookHandlerInterface::class)
            ->addTag(WebhookHandlerInterface::class);

        $container->setParameter('storyblok_api.base_uri', $config['base_uri']);
        $container->setParameter('storyblok_api.token', $config['token']);
        $container->setParameter('storyblok_api.webhooks.secret', $config['webhook_secret']);
        $container->setParameter('storyblok_api.version', $config['version']);

        if (\array_key_exists('assets_token', $config)) {
            $container->setParameter('storyblok_api.assets_token', $config['assets_token']);
            self::configureAssetsApi($container);
            $container->setAlias(StoryblokClientInterface::class, StoryblokClient::class);
        }

        self::configureMaker($container, $config);

        self::configureCdn($container, $config);

        if (false === $container->getParameter('kernel.debug')) {
            $container->removeDefinition(StoryblokCollector::class);
            $container->removeDefinition(UpdateProfilerListener::class);

            if ($config['cdn']['enabled']) {
                $container->removeDefinition(CdnCollector::class);
                $container->removeDefinition(TraceableCdnStorage::class);
            }
        } else {
            $httpClient = $container->getDefinition('storyblok.http_client');

            $container->setDefinition('storyblok.http_client', new Definition(
                class: TraceableHttpClient::class,
                arguments: [
                    '$client' => $httpClient,
                ],
            ));

            if ($config['cdn']['enabled'] && 'filesystem' === $config['cdn']['storage']['type']) {
                $container->setAlias(CdnStorageInterface::class, TraceableCdnStorage::class);
            }
        }

        if (true === $config['auto_resolve_relations'] || true === $config['auto_resolve_links']) {
            $storiesApi = new Definition(StoriesApi::class, [
                '$client' => $container->getDefinition(StoryblokClient::class),
                '$version' => $container->getParameter('storyblok_api.version'),
            ]);

            $resolver = new Definition(StoryResolver::class);
            $container->setAlias(ResolverInterface::class, StoryResolver::class);

            $resolvedStoriesApi = new Definition(StoriesResolvedApi::class, [
                '$storiesApi' => $storiesApi,
                '$resolver' => $resolver,
                '$resolveRelations' => true === $config['auto_resolve_relations'],
                '$resolveLinks' => true === $config['auto_resolve_links'],
            ]);

            $container->setDefinition(StoriesResolvedApi::class, $resolvedStoriesApi);
            $container->setAlias(StoriesApiInterface::class, StoriesResolvedApi::class);
        }

        if (false === $config['controller']['ascending_redirect_fallback']) {
            $container->removeDefinition(StoryNotFoundExceptionListener::class);
        }

        $storage = $container->getDefinition(GlobalCachingListener::class);
        $storage->setArguments([
            '$public' => $config['controller']['cache']['public'],
            '$mustRevalidate' => $config['controller']['cache']['must_revalidate'],
            '$maxAge' => $config['controller']['cache']['max_age'],
            '$smaxAge' => $config['controller']['cache']['smax_age'],
            '$etag' => $config['controller']['cache']['etag'],
        ]);

        $container->setDefinition(GlobalCachingListener::class, $storage);

        $this->registerAttributes($container, $config);
    }

    /**
     * Registers the "make:storyblok:block" maker command when the Management API credentials
     * are configured. Both "management_token" and "space_id" must be set together (enforced by
     * {@see Configuration}).
     *
     * @param array<string, mixed> $config
     */
    private static function configureMaker(ContainerBuilder $container, array $config): void
    {
        if (null === $config['management_token'] && null === $config['space_id']) {
            return;
        }

        $container->setParameter('storyblok_api.management_token', $config['management_token']);
        $container->setParameter('storyblok_api.space_id', $config['space_id']);

        // The maker command and the Management API client ship as dev dependencies
        // (require-dev + suggest) and are not installed in every application. If the maker is
        // configured but a package is missing, guide the developer with an actionable message
        // instead of failing with a cryptic "class does not exist" error. The parameters above
        // are set unconditionally so the configured env vars are consumed.
        if (!class_exists(AbstractMaker::class)) {
            throw new \LogicException('The "make:storyblok:block" maker is configured ("storyblok.management_token" / "storyblok.space_id"), but symfony/maker-bundle is not installed. Try running "composer require --dev symfony/maker-bundle".');
        }

        if (!class_exists(ManagementApiClient::class)) {
            throw new \LogicException('The "make:storyblok:block" maker is configured ("storyblok.management_token" / "storyblok.space_id"), but storyblok/php-management-api-client is not installed. Try running "composer require --dev storyblok/php-management-api-client".');
        }

        $container->setDefinition(ManagementApiClient::class, new Definition(ManagementApiClient::class, [
            '$personalAccessToken' => '%storyblok_api.management_token%',
        ]));

        $container->setDefinition(ComponentApi::class, new Definition(ComponentApi::class, [
            '$managementClient' => new Reference(ManagementApiClient::class),
            '$spaceId' => '%storyblok_api.space_id%',
        ]));

        $container->setDefinition(
            ManagementApiComponentProvider::class,
            (new Definition(ManagementApiComponentProvider::class))->setAutowired(true),
        );
        $container->setAlias(ComponentProviderInterface::class, ManagementApiComponentProvider::class);

        $container->setDefinition(SchemaMapper::class, new Definition(SchemaMapper::class));

        $container->setDefinition(
            MakeStoryblokBlock::class,
            (new Definition(MakeStoryblokBlock::class))
                ->setAutowired(true)
                ->addTag('maker.command'),
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function configureCdn(ContainerBuilder $container, array $config): void
    {
        if (false === $config['cdn']['enabled']) {
            // Remove all CDN-related services
            $container->removeDefinition(CdnController::class);
            $container->removeDefinition(CdnUrlGenerator::class);
            $container->removeDefinition(CdnFilesystemStorage::class);
            $container->removeDefinition(AssetDownloader::class);
            $container->removeDefinition(CdnExtension::class);
            $container->removeDefinition(CdnCleanupCommand::class);
            $container->removeDefinition(CdnCollector::class);
            $container->removeDefinition(TraceableCdnStorage::class);

            if ($container->hasAlias(CdnStorageInterface::class)) {
                $container->removeAlias(CdnStorageInterface::class);
            }

            if ($container->hasAlias(CdnUrlGeneratorInterface::class)) {
                $container->removeAlias(CdnUrlGeneratorInterface::class);
            }

            if ($container->hasAlias(FileDownloaderInterface::class)) {
                $container->removeAlias(FileDownloaderInterface::class);
            }

            return;
        }

        // Configure CDN controller cache settings
        $cdnController = $container->getDefinition(CdnController::class);
        $cdnController->setArgument('$public', $config['cdn']['cache']['public']);
        $cdnController->setArgument('$maxAge', $config['cdn']['cache']['max_age']);
        $cdnController->setArgument('$smaxAge', $config['cdn']['cache']['smax_age']);
        $cdnController->setArgument('$etag', $config['cdn']['cache']['etag']);

        if ('filesystem' === $config['cdn']['storage']['type']) {
            // Configure filesystem storage with the provided path
            $filesystemStorage = $container->getDefinition(CdnFilesystemStorage::class);
            $filesystemStorage->setArgument('$storagePath', $config['cdn']['storage']['path']);

            // Configure cleanup command with the same path
            $cleanupCommand = $container->getDefinition(CdnCleanupCommand::class);
            $cleanupCommand->setArgument('$storagePath', $config['cdn']['storage']['path']);
        } else {
            // Custom storage: remove filesystem-specific services
            // User must provide their own CdnFileStorageInterface implementation
            $container->removeDefinition(CdnFilesystemStorage::class);
            $container->removeDefinition(TraceableCdnStorage::class);
            $container->removeDefinition(CdnCleanupCommand::class);
            $container->removeDefinition(CdnCollector::class);

            if ($container->hasAlias(CdnStorageInterface::class)) {
                $container->removeAlias(CdnStorageInterface::class);
            }
        }
    }

    private static function configureAssetsApi(ContainerBuilder $container): void
    {
        $client = new Definition(ScopingHttpClient::class);
        $client->setFactory([ScopingHttpClient::class, 'forBaseUri']);
        $client->setArguments([
            '$client' => $container->getDefinition('storyblok.http_client'),
            '$baseUri' => $container->getParameter('storyblok_api.base_uri'),
            '$defaultOptions' => [
                'query' => [
                    'token' => $container->getParameter('storyblok_api.assets_token'),
                ],
            ],
        ]);

        $container->setDefinition('storyblok.assets.scoped_http_client', $client);

        $definition = new Definition(StoryblokClient::class, [
            '$baseUri' => $container->getParameter('storyblok_api.base_uri'),
            '$token' => $container->getParameter('storyblok_api.assets_token'),
        ]);

        $definition->addMethodCall(
            'withHttpClient',
            [$container->getDefinition('storyblok.assets.scoped_http_client')],
        );

        $container->setDefinition('storyblok.assets_client', $definition);

        $container->setDefinition(
            AssetsApi::class,
            new Definition(AssetsApi::class, [
                '$client' => $container->getDefinition('storyblok.assets_client'),
            ]),
        );

        $container->setAlias(AssetsApiInterface::class, AssetsApi::class);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerAttributes(ContainerBuilder $container, array $config): void
    {
        $container->registerAttributeForAutoconfiguration(AsBlock::class, static function (
            ChildDefinition $definition,
            AsBlock $attribute,
            \ReflectionClass $reflector,
        ) use ($container, $config): void {
            $name = $attribute->name ?? u($reflector->getShortName())->snake()->toString();
            $template = $attribute->template ?? \sprintf('%s/%s.html.twig', $config['blocks_template_path'], $name);

            $registryDefinition = $container->getDefinition(BlockRegistry::class);
            $registryDefinition->addMethodCall('add', [[
                'className' => $reflector->getName(),
                'name' => $name,
                'template' => $template,
            ]]);
        });

        $container->registerAttributeForAutoconfiguration(AsContentTypeController::class, static function (
            ChildDefinition $definition,
            AsContentTypeController $attribute,
            \ReflectionClass $reflector,
        ) use ($container): void {
            $registryDefinition = $container->getDefinition(ContentTypeControllerRegistry::class);
            $registryDefinition->addMethodCall('add', [[
                'className' => $reflector->getName(),
                'contentType' => $attribute->contentType,
                'type' => $attribute->contentType::type(),
                'slug' => $attribute->slug,
                'resolveRelations' => $attribute->resolveRelations->toString(),
                'resolveLinks' => $attribute->resolveLinks->toArray(),
            ]]);

            $definition->addTag('storyblok.content_type.controller');
            $definition->addTag('controller.service_arguments');
        });
    }
}
