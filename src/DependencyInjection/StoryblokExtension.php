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
use Storyblok\Bundle\ContentType\Attribute\AsContentTypeController;
use Storyblok\Bundle\ContentType\ContentTypeControllerRegistry;
use Storyblok\Bundle\ContentType\Listener\GlobalCachingListener;
use Storyblok\Bundle\ContentType\Listener\StoryNotFoundExceptionListener;
use Storyblok\Bundle\DataCollector\StoryblokCollector;
use Storyblok\Bundle\Listener\UpdateProfilerListener;
use Storyblok\Bundle\Webhook\Handler\WebhookHandlerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
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

        if (false === $container->getParameter('kernel.debug')) {
            $container->removeDefinition(StoryblokCollector::class);
            $container->removeDefinition(UpdateProfilerListener::class);
        } else {
            $httpClient = $container->getDefinition('storyblok.http_client');

            $container->setDefinition('storyblok.http_client', new Definition(
                class: TraceableHttpClient::class,
                arguments: [
                    '$client' => $httpClient,
                ],
            ));
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
        ]);

        $container->setDefinition(GlobalCachingListener::class, $storage);

        $this->registerAttributes($container, $config);
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
            ]]);

            $definition->addTag('storyblok.content_type.controller');
            $definition->addTag('controller.service_arguments');
        });
    }
}
