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

namespace Storyblok\Bundle\ContentType\Request;

use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Api\Request\StoryRequest;
use Storyblok\Api\Response\StoryResponse;
use Storyblok\Api\StoriesApiInterface;
use Storyblok\Bundle\Routing\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * When a slug was not found it tries to find the parent slug and redirects to it. When this fails it throws a
 * HttpNotFoundException.
 */
final readonly class SmoothRedirectRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private StoriesApiInterface $stories,
        private UrlGeneratorInterface $urlGenerator,
        private string $version,
    ) {
    }

    public function handle(Request $request, string $slug): Response|StoryResponse
    {
        try {
            return $this->stories->bySlug($slug, new StoryRequest(
                language: $request->getLocale(),
                version: Version::from($this->version),
            ));
        } catch (ClientExceptionInterface|\InvalidArgumentException|\ValueError) {
            if (1 === \count($parts = \explode('/', $slug))) {
                throw new NotFoundHttpException(\sprintf('Story with slug "%s" not found.', $slug));
            }

            try {
                array_pop($parts);
                $this->stories->bySlug($parentSlug = implode('/', $parts), new StoryRequest(
                    language: $request->getLocale(),
                    version: Version::from($this->version),
                ));

                return new RedirectResponse(
                    url: $this->urlGenerator->generate(Route::STORYBLOK_CONTENT_TYPE, ['slug' => $parentSlug]),
                    status: Response::HTTP_MOVED_PERMANENTLY,
                );
            } catch (ClientExceptionInterface|\InvalidArgumentException|\ValueError) {
                throw new NotFoundHttpException(\sprintf('Story with slug "%s" not found.', $slug));
            }
        }
    }
}
