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

namespace Storyblok\Bundle\ContentType\Attribute;

use Storyblok\Bundle\ContentType\ContentTypeInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsContentTypeController
{
    /**
     * @param class-string<ContentTypeInterface> $contentType
     */
    public function __construct(
        public string $contentType,
        public ?string $slug = null,
    ) {
    }
}
