<?php

declare(strict_types=1);

namespace Symfony\Component\Routing\Loader\Configurator;

use Storyblok\Bundle\Controller\CdnController;
use Storyblok\Bundle\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

return function (RoutingConfigurator $routes): void {
    $routes->add(Route::CDN, '/f/{id}/{filename}.{extension}')
        ->controller(CdnController::class)
        ->methods([Request::METHOD_GET, Request::METHOD_HEAD])
        ->requirements([
            'id' => '[a-f0-9]{16}',
            'extension' => 'jpe?g|png|gif|webp|svg|avif|pdf|mp4',
        ])
        ->options([
            'priority' => 1,
        ]);
};
