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

use OskarStark\Value\TrimmedNonEmptyString;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Api\Request\StoryRequest;
use Storyblok\Api\StoriesApiInterface;
use Storyblok\Bundle\ContentType\ContentTypeControllerRegistry;
use Storyblok\Bundle\ContentType\ContentTypeInterface;
use Storyblok\Bundle\ContentType\ContentTypeStorageInterface;
use Storyblok\Bundle\ContentType\Exception\ContentTypeControllerNotFoundException;
use Storyblok\Bundle\ContentType\Exception\InvalidStoryException;
use Storyblok\Bundle\ContentType\Exception\StoryNotFoundException;
use Storyblok\Bundle\ContentType\UnicodeSlug;
use Storyblok\Bundle\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Webmozart\Assert\Assert;

final readonly class ResolveControllerListener
{
    public function __construct(
        private StoriesApiInterface $stories,
        private ContainerInterface $container,
        private ContentTypeControllerRegistry $registry,
        private ContentTypeStorageInterface $storage,
        private LoggerInterface $logger,
        private string $version,
    ) {
    }

    public function __invoke(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if ($request->attributes->get('_route') !== Route::CONTENT_TYPE) {
            return;
        }

        $params = $request->attributes->get('_route_params', []);

        if (!\array_key_exists('slug', $params)) {
            return;
        }

        $slug = new UnicodeSlug($params['slug']);

        try {
            $response = $this->stories->bySlug($slug->toString(), new StoryRequest(
                language: $request->getLocale(),
                version: Version::from($this->version),
            ));

            $story = $response->story;
            Assert::keyExists($story, 'default_full_slug');
            Assert::keyExists($story, 'full_slug');
            $slug = $story['default_full_slug'] ?? $story['full_slug'];

            Assert::keyExists($story, 'content');
            Assert::keyExists($story['content'], 'component');
        } catch (ClientExceptionInterface|\InvalidArgumentException|\ValueError) {
            throw new StoryNotFoundException(\sprintf('Story with slug "%s" not found.', $slug));
        }

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

        try {
            /** @var ContentTypeInterface $contentType */
            $contentType = new $definition->contentType($story);
        } catch (\Throwable $e) {
            throw new InvalidStoryException($e->getMessage(), $e->getCode(), $e);
        }

        $this->storage->setContentType($contentType);

        $request->attributes->set('_storyblok_content_type', $definition->contentType);

        $event->setController($controller);
    }

    public static function noop(): never
    {
        throw new \LogicException('This method should never be called. This method is only used for the route definition.');
    }
}
