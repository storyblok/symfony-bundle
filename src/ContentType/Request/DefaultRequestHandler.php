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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * This will be used as the default request handler for all content types.
 * If a story is can not be found this throws a HttpNotFoundException.
 *
 * @see \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
 */
final readonly class DefaultRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private StoriesApiInterface $stories,
        private string $version,
    ) {
    }

    public function handle(Request $request, string $slug): StoryResponse
    {
        try {
            return $this->stories->bySlug($slug, new StoryRequest(
                language: $request->getLocale(),
                version: Version::from($this->version),
            ));
        } catch (ClientExceptionInterface|\InvalidArgumentException|\ValueError) {
            throw new NotFoundHttpException(\sprintf('Story with slug "%s" not found.', $slug));
        }
    }
}
