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

namespace Storyblok\Bundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Api\AssetsApi;
use Storyblok\Api\AssetsApiInterface;
use Storyblok\Api\Resolver\ResolverInterface;
use Storyblok\Api\StoriesResolvedApi;
use Storyblok\Api\StoryblokClientInterface;
use Storyblok\Bundle\ContentType\Listener\StoryNotFoundExceptionListener;
use Storyblok\Bundle\DataCollector\StoryblokCollector;
use Storyblok\Bundle\DependencyInjection\StoryblokExtension;
use Storyblok\Bundle\Listener\UpdateProfilerListener;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpClient\TraceableHttpClient;

final class StoryblokExtensionTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function loadWillSetParameters(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', $faker->boolean());

        $config = [
            ['base_uri' => $baseUri = $faker->url()],
            ['token' => $token = $faker->uuid()],
            ['version' => $version = $faker->randomElement(['draft', 'published'])],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertSame($baseUri, $builder->getParameter('storyblok_api.base_uri'));
        self::assertSame($token, $builder->getParameter('storyblok_api.token'));
        self::assertSame($version, $builder->getParameter('storyblok_api.version'));
    }

    #[Test]
    public function loadWithoutKernelDebugWillRemoveDefinitions(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', false);

        $config = [
            ['base_uri' => $faker->url()],
            ['token' => $faker->uuid()],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertFalse($builder->hasDefinition(StoryblokCollector::class));
        self::assertFalse($builder->hasDefinition(UpdateProfilerListener::class));
    }

    #[Test]
    public function loadWithKernelDebugWillReplaceHttpClientWithTracableHttpClient(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', true);

        $config = [
            ['base_uri' => $faker->url()],
            ['token' => $faker->uuid()],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertTrue($builder->hasDefinition('storyblok.http_client'));

        $definition = $builder->getDefinition('storyblok.http_client');

        self::assertSame(TraceableHttpClient::class, $definition->getClass());
    }

    #[Test]
    public function loadWithoutAssetsToken(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', true);

        $config = [
            ['base_uri' => $faker->url()],
            ['token' => $faker->uuid()],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertFalse($builder->hasDefinition('storyblok.assets.scoped_http_client'));
        self::assertFalse($builder->hasDefinition('storyblok.assets_client'));
        self::assertFalse($builder->hasAlias(AssetsApiInterface::class));
        self::assertTrue($builder->hasAlias(StoryblokClientInterface::class));
        self::assertFalse($builder->hasDefinition(AssetsApi::class));
        self::assertFalse($builder->hasParameter('storyblok_api.assets_token'));
    }

    #[Test]
    public function loadWithAssetsToken(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', true);

        $config = [
            ['base_uri' => $faker->url()],
            ['token' => $faker->uuid()],
            ['assets_token' => $token = $faker->uuid()],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertTrue($builder->hasDefinition('storyblok.assets.scoped_http_client'));
        self::assertTrue($builder->hasDefinition('storyblok.assets_client'));
        self::assertTrue($builder->hasAlias(AssetsApiInterface::class));
        self::assertTrue($builder->hasAlias(StoryblokClientInterface::class));
        self::assertTrue($builder->hasDefinition(AssetsApi::class));

        self::assertSame($token, $builder->getParameter('storyblok_api.assets_token'));
    }

    #[Test]
    public function loadWithAutoResolveStoriesTrue(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', true);

        $config = [
            ['base_uri' => $faker->url()],
            ['token' => $faker->uuid()],
            ['auto_resolve_relations' => true],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertTrue($builder->hasAlias(ResolverInterface::class));
        self::assertTrue($builder->hasDefinition(StoriesResolvedApi::class));
    }

    #[Test]
    public function loadWithoutAscendingRedirectFallbackWillRemoveTheServiceDefinition(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', true);

        $config = [
            ['base_uri' => $faker->url()],
            ['token' => $faker->uuid()],
            ['controller' => ['ascending_redirect_fallback' => false]],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertFalse($builder->hasDefinition(StoryNotFoundExceptionListener::class));
    }

    #[Test]
    public function loadWithAscendingRedirectFallbackWillRemoveTheServiceDefinition(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', true);

        $config = [
            ['base_uri' => $faker->url()],
            ['token' => $faker->uuid()],
            ['controller' => ['ascending_redirect_fallback' => true]],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertTrue($builder->hasDefinition(StoryNotFoundExceptionListener::class));
    }
}
