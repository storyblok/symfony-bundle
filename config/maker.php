<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Storyblok\Bundle\Maker\MakeStoryblokBlock;
use Storyblok\Bundle\Maker\SchemaMapper;
use Storyblok\Bundle\Maker\Storyblok\ComponentProviderInterface;
use Storyblok\Bundle\Maker\Storyblok\ManagementApiComponentProvider;
use Storyblok\ManagementApi\Endpoints\ComponentApi;
use Storyblok\ManagementApi\ManagementApiClient;

/*
 * Services for the "make:storyblok:block" maker command.
 *
 * This file is loaded conditionally by StoryblokExtension: only when both
 * symfony/maker-bundle and storyblok/php-management-api-client are installed and the
 * "management_token" and "space_id" options are configured. It must never be loaded
 * unconditionally, otherwise the container would try to autowire MakeStoryblokBlock
 * (and its MakerBundle parent) for consumers that do not have maker-bundle installed.
 */
return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()

        ->set(ManagementApiClient::class)
            ->args([
                '$personalAccessToken' => param('storyblok_api.management_token'),
            ])

        ->set(ComponentApi::class)
            ->args([
                '$managementClient' => service(ManagementApiClient::class),
                '$spaceId' => param('storyblok_api.space_id'),
            ])

        ->set(ManagementApiComponentProvider::class)
            ->alias(ComponentProviderInterface::class, ManagementApiComponentProvider::class)

        ->set(SchemaMapper::class)

        ->set(MakeStoryblokBlock::class)
            ->tag('maker.command')
    ;
};
