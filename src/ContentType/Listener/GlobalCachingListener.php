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

namespace Storyblok\Bundle\ContentType\Listener;

use Storyblok\Bundle\ContentType\ContentTypeStorageInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final readonly class GlobalCachingListener
{
    public function __construct(
        private ContentTypeStorageInterface $storage,
        private ?bool $public = null,
        private ?bool $mustRevalidate = null,
        private ?int $maxAge = null,
        private ?int $smaxAge = null,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if ([] !== $event->getRequest()->get('_cache', [])) {
            return;
        }

        $response = $event->getResponse();

        if (null !== $this->public && !$response->headers->hasCacheControlDirective('public')) {
            if ($this->public) {
                $response->setPublic();
            } else {
                $response->setPrivate();
            }
        }

        if (null !== $this->mustRevalidate && !$response->headers->hasCacheControlDirective('must-revalidate')) {
            $response->headers->addCacheControlDirective('must-revalidate');

            $contentType = $this->storage->getContentType();

            if (null !== $contentType && !$response->headers->has('Last-Modified')) {
                $response->setLastModified($contentType->publishedAt());
            }
        }

        if (null !== $this->maxAge && !$response->headers->hasCacheControlDirective('max-age')) {
            $response->setMaxAge($this->maxAge);
        }

        if (null !== $this->smaxAge && !$response->headers->hasCacheControlDirective('s-maxage')) {
            $response->setSharedMaxAge($this->smaxAge);
        }

        $event->setResponse($response);
    }
}
