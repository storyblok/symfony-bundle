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

namespace Storyblok\Bundle\ValueResolver;

use Storyblok\Bundle\ContentType\ContentTypeInterface;
use Storyblok\Bundle\ContentType\ContentTypeStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final readonly class ContentTypeValueResolver implements ValueResolverInterface
{
    public function __construct(
        private ContentTypeStorageInterface $storage,
    ) {
    }

    /**
     * @return iterable<ContentTypeInterface>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$request->attributes->has('_storyblok_content_type')) {
            return [];
        }

        $types = \array_filter(
            \explode('|', $argument->getType()),
            static fn (string $type) => $request->attributes->get('_storyblok_content_type') === $type,
        );

        if (\count($types) === 0) {
            return [];
        }

        $contentType = $this->storage->getContentType();

        if (null === $contentType) {
            return [];
        }

        return [$contentType];
    }
}
