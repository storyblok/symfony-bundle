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

namespace Storyblok\Bundle\Tests\Util;

use Storyblok\Bundle\StoryblokBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    public function __construct(
        string $environment,
        bool $debug,
        private readonly \Closure $configureContainerBuilder,
    ) {
        parent::__construct(
            $environment,
            $debug,
        );
    }

    public function registerBundles(): iterable
    {
        return [
            StoryblokBundle::class => new StoryblokBundle(),
        ];
    }

    /**
     * @phpstan-param class-string $testClassName
     */
    public static function create(array $options, string $testClassName, \Closure $configureContainerBuilder): self
    {
        return new self(
            self::environment($options),
            self::debug($options),
            $configureContainerBuilder,
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $configureContainerBuilder = $this->configureContainerBuilder;

        $loader->load(static function (ContainerBuilder $containerBuilder) use ($configureContainerBuilder): void {
            $configureContainerBuilder($containerBuilder);
        });
    }

    private static function environment(array $options): string
    {
        return $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
    }

    private static function debug(array $options): bool
    {
        if (isset($options['debug'])) {
            return (bool) $options['debug'];
        }

        if (isset($_ENV['APP_DEBUG'])) {
            return (bool) $_ENV['APP_DEBUG'];
        }

        if (isset($_SERVER['APP_DEBUG'])) {
            return (bool) $_SERVER['APP_DEBUG'];
        }

        return true;
    }
}
