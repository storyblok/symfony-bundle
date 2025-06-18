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

namespace Storyblok\Bundle\Tests\Double\ContentType;

use Safe\DateTimeImmutable;
use Storyblok\Bundle\ContentType\ContentType;

final readonly class FailingContentType extends ContentType
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values)
    {
        throw new \InvalidArgumentException('This is a failing content type for testing purposes.');
    }

    public function publishedAt(): \DateTimeInterface
    {
        return new DateTimeImmutable();
    }
}
