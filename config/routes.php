<?php

declare(strict_types=1);

namespace Symfony\Component\Routing\Loader\Configurator;

use Storyblok\Bundle\Controller\ContentTypeController;
use Storyblok\Bundle\Controller\WebhookController;
use Storyblok\Bundle\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

return function (RoutingConfigurator $routes): void {
    $routes->add(Route::WEBHOOK, '/webhook/storyblok')
        ->controller(WebhookController::class)
        ->methods([Request::METHOD_POST])
        ->options([
            'priority' => 1,
        ]);

    $routes->add(Route::STORYBLOK_CONTENT_TYPE, '/{!slug}')
        ->controller(ContentTypeController::class)
        ->requirements([
            'slug' => ContentTypeController::PATH_PATTERN,
        ])
        ->options([
            'priority' => -10000,
        ]);
};
