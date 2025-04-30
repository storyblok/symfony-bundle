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

namespace Storyblok\Bundle\Tests\Double\ContentType;

use Safe\DateTimeImmutable;
use Storyblok\Bundle\ContentType\ContentType;
use Webmozart\Assert\Assert;

final readonly class SampleContentType extends ContentType
{
    private DateTimeImmutable $publishedAt;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values)
    {
        $publishedAt = new DateTimeImmutable();

        if (\array_key_exists('published_at', $values)) {
            Assert::string($values['published_at']);
            $publishedAt = new DateTimeImmutable($values['published_at']);
        }

        $this->publishedAt = $publishedAt;
    }

    public function publishedAt(): \DateTimeInterface
    {
        return $this->publishedAt;
    }
}
