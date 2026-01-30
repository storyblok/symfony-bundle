<?php

declare(strict_types=1);

namespace Symfony\Component\Routing\Loader\Configurator;

return function (RoutingConfigurator $routes): void {
    $routes->import('@StoryblokBundle/config/routes/webhook.php');
    $routes->import('@StoryblokBundle/config/routes/content_type.php');
    $routes->import('@StoryblokBundle/config/routes/cdn.php');
};
