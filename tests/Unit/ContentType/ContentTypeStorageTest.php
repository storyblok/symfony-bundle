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

namespace Storyblok\Bundle\Tests\Unit\ContentType;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\ContentType\ContentTypeStorage;
use Storyblok\Bundle\Tests\Double\ContentType\SampleContentType;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class ContentTypeStorageTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function defaults(): void
    {
        self::assertNull((new ContentTypeStorage())->getContentType());
    }

    #[Test]
    public function canSetAndGetContentType(): void
    {
        $expected = new SampleContentType([]);

        $storage = new ContentTypeStorage();

        $storage->setContentType($expected);

        self::assertSame($expected, $storage->getContentType());
    }
}
