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

namespace Storyblok\Bundle\Controller;

use OskarStark\Value\TrimmedNonEmptyString;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Safe\DateTimeImmutable;
use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Bundle\ContentType\ContentTypeControllerRegistry;
use Storyblok\Bundle\ContentType\ContentTypeInterface;
use Storyblok\Bundle\ContentType\Exception\ContentTypeControllerNotFoundException;
use Storyblok\Bundle\ContentType\Request\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

final readonly class ContentTypeController
{
    /**
     *  \p{L} → Matches any letter (Latin, Kanji, Hiragana, Katakana, etc.)
     *  \p{N} → Matches any number (so numbers remain valid in slugs)
     *  (?:-[\p{L}\p{N}]+)* → Allows hyphenated words (e.g., hello-world)
     *  (?:\/[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*)*\/? → Allows slashes for hierarchical paths
     *  Trailing slash (\/?) → Optional to allow both /slug and /slug/.
     */
    public const string PATH_PATTERN = '([\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*(?:\/[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*)*\/?)$';
    private Version $version;

    public function __construct(
        private ContentTypeControllerRegistry $registry,
        private ContainerInterface $container,
        private LoggerInterface $logger,
        private RequestHandlerInterface $requestHandler,
        private bool $public,
        private bool $mustRevalidate,
        private int $maxAge,
        private int $smaxAge,
        string $version,
    ) {
        $this->version = Version::from($version);
    }

    public function __invoke(Request $request, string $slug): Response
    {
        $response = $this->requestHandler->handle($request, $slug);

        if ($response instanceof Response) {
            return $response;
        }

        $story = $response->story;
        Assert::keyExists($story, 'published_at');
        Assert::keyExists($story, 'content');
        Assert::keyExists($story['content'], 'component');

        try {
            $definition = $this->registry->bySlug(\sprintf('/%s', $slug));
            $this->logger->debug('Found content type controller by slug.', [
                'slug' => $slug,
                'controller' => $definition->className,
            ]);
        } catch (ContentTypeControllerNotFoundException) {
            $type = TrimmedNonEmptyString::fromString($story['content']['component'])->toString();
            $definition = $this->registry->byType($type);
            $this->logger->debug('Found content type controller by component.', [
                'type' => $type,
                'controller' => $definition->className,
            ]);
        }

        /** @var callable(Request, ContentTypeInterface): Response $controller */
        $controller = $this->container->get($definition->className);

        /** @var ContentTypeInterface $contentType */
        $contentType = new $definition->dto($story);

        $this->logger->debug('Calling content type controller.', [
            'controller' => $definition->className,
            'content_type' => $contentType,
        ]);

        $response = $controller($request, $contentType);

        $this->logger->debug('Content type controller returned response.', [
            'controller' => $definition->className,
            'response' => $response,
        ]);

        if ($this->version->equals(Version::Published)) {
            if ($this->public) {
                $response->setPublic();
            } else {
                $response->setPrivate();
            }

            if ($this->mustRevalidate) {
                $response->mustRevalidate();
            }

            $response->setMaxAge($this->maxAge);
            $response->setSharedMaxAge($this->smaxAge);
            $response->setLastModified(new DateTimeImmutable($story['published_at']));
        }

        return $response;
    }
}
