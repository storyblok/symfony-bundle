<?php

declare(strict_types=1);

/**
 * This file is part of sensiolabs-de/storyblok-bundle.
 *
 * (c) SensioLabs Deutschland <info@sensiolabs.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Storyblok\Bundle\Listener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

final readonly class CacheAwareResponseListener
{
    public function __construct(
        private bool $debug,
        private bool $public,
        private bool $mustRevalidate,
        private int $maxAge,
        private int $smaxAge,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Don't enable caching in debug mode
        //        if ($this->debug) {
        //            return;
        //        }

        $response = $event->getResponse();

        dd($response->headers->all());

        // If caching is already set in the response, do not override it.
        if ($response->headers->has('Cache-Control')) {
            return;
        }

        $response->setCache([
            'public' => $this->public,
            'must_revalidate' => $this->mustRevalidate,
            'max_age' => $this->maxAge,
            's_maxage' => $this->smaxAge,
        ]);

        $event->setResponse($response);
    }
}
