<?php

declare(strict_types=1);

namespace Symfony\Component\Routing\Loader\Configurator;

use Storyblok\Bundle\ContentType\Listener\ResolveControllerListener;
use Storyblok\Bundle\Routing\Requirement;
use Storyblok\Bundle\Routing\Route;

return function (RoutingConfigurator $routes): void {
    $routes->add(Route::CONTENT_TYPE, '/{!slug}')
        /**
         * Symfony enforces a callable as string here in order to register a route.
         */
        ->controller(\sprintf('%s::noop', ResolveControllerListener::class))
        ->requirements([
            'slug' => Requirement::SLUG,
        ])
        ->options([
            'priority' => -10000,
        ]);
};
