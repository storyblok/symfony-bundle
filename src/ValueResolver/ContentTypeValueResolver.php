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

        if ($argument->getType() !== $request->attributes->get('_storyblok_content_type')) {
            return [];
        }

        $contentType = $this->storage->getContentType();

        if (null === $contentType) {
            return [];
        }

        return [$contentType];
    }
}
