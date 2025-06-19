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

namespace Storyblok\Bundle\Tests\Unit\ContentType;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Tests\Double\ContentType\SampleContentType;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class ContentTypeTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function type(): void
    {
        self::assertSame('sample_content_type', SampleContentType::type());
    }
}
