<?php

declare(strict_types=1);

namespace Symfony\Component\Routing\Loader\Configurator;

use Storyblok\Bundle\Controller\WebhookController;
use Symfony\Component\HttpFoundation\Request;

return function (RoutingConfigurator $routes): void {
    $routes->add('storyblok_webhook', '/webhook/storyblok')
        ->controller(WebhookController::class)
        ->methods([Request::METHOD_POST])
        ->options([
            'priority' => 1,
        ]);
};
