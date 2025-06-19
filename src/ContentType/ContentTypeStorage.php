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

namespace Storyblok\Bundle\ContentType;

/**
 * @internal
 */
final class ContentTypeStorage implements ContentTypeStorageInterface
{
    private ?ContentTypeInterface $contentType = null;

    public function setContentType(ContentTypeInterface $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function getContentType(): ?ContentTypeInterface
    {
        return $this->contentType;
    }
}
