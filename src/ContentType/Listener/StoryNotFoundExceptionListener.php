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

namespace Storyblok\Bundle\ContentType\Listener;

use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Api\Request\StoryRequest;
use Storyblok\Api\StoriesApiInterface;
use Storyblok\Bundle\ContentType\Exception\StoryNotFoundException;
use Storyblok\Bundle\Routing\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

final readonly class StoryNotFoundExceptionListener
{
    public function __construct(
        private StoriesApiInterface $stories,
        private UrlGeneratorInterface $urlGenerator,
        private string $version,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$event->getThrowable() instanceof StoryNotFoundException) {
            return;
        }

        $request = $event->getRequest();

        if ($request->get('_route') !== Route::CONTENT_TYPE) {
            return;
        }

        $params = $request->get('_route_params', []);

        if (!\array_key_exists('slug', $params)) {
            return;
        }

        $slug = $params['slug'];

        if (1 === \count($parts = \explode('/', $slug))) {
            throw new StoryNotFoundException(\sprintf('Story with slug "%s" not found.', $slug));
        }

        try {
            array_pop($parts);
            $this->stories->bySlug($parentSlug = implode('/', $parts), new StoryRequest(
                language: $request->getLocale(),
                version: Version::from($this->version),
            ));

            $event->setResponse(new RedirectResponse(
                url: $this->urlGenerator->generate(Route::CONTENT_TYPE, ['slug' => $parentSlug]),
                status: Response::HTTP_FOUND,
            ));
        } catch (ClientExceptionInterface|\InvalidArgumentException|\ValueError) {
            throw new StoryNotFoundException(\sprintf('Story with slug "%s" not found.', $slug));
        }
    }
}
