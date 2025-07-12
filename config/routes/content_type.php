<?php

declare(strict_types=1);

namespace Symfony\Component\Routing\Loader\Configurator;

use Storyblok\Bundle\ContentType\Listener\ResolveControllerListener;
use Storyblok\Bundle\Routing\Route;

return function (RoutingConfigurator $routes): void {
    $routes->add(Route::CONTENT_TYPE, '/{!slug}')
        /**
         * Symfony enforces a callable as string here in order to register a route.
         */
        ->controller(\sprintf('%s::noop', ResolveControllerListener::class))
        ->requirements([
            /**
             *  \p{L} → Matches any letter (Latin, Kanji, Hiragana, Katakana, etc.)
             *  \p{N} → Matches any number (so numbers remain valid in slugs)
             *  (?:-[\p{L}\p{N}]+)* → Allows hyphenated words (e.g., hello-world)
             *  (?:\/[\p{L}\p{N}]+(?:[-_][\p{L}\p{N}]+)*)*\/? → Allows slashes for hierarchical paths
             *  Trailing slash (\/?) → Optional to allow both /slug and /slug/.
             */
            'slug' => '([\p{L}\p{N}]+(?:[-_][\p{L}\p{N}]+)*(?:\/[\p{L}\p{N}]+(?:[-_][\p{L}\p{N}]+)*)*\/?)$',
        ])
        ->options([
            'priority' => -10000,
        ]);
};
