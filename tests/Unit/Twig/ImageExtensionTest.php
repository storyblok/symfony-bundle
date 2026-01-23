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

namespace Storyblok\Bundle\Tests\Unit\Twig;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Api\Domain\Type\Asset;
use Storyblok\Bundle\Twig\ImageExtension;
use Storyblok\ImageService\Image;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class ImageExtensionTest extends TestCase
{
    #[Test]
    public function getFilters(): void
    {
        $filters = (new ImageExtension())->getFilters();

        self::assertCount(1, $filters);
        self::assertSame('storyblok_image', $filters[0]->getName());
    }

    #[Test]
    public function imageReturnsImageInstance(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg',
        ]);

        $image = (new ImageExtension())->image($asset);

        self::assertInstanceOf(Image::class, $image);
        self::assertSame('https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg', (string) $image);
    }

    #[Test]
    public function imageWithResize(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg',
        ]);

        $image = (new ImageExtension())->image($asset, 640, 480);

        self::assertInstanceOf(Image::class, $image);
        self::assertStringEndsWith('/m/640x480', (string) $image);
    }

    #[Test]
    public function imageWithoutResizeWhenBothDimensionsAreZero(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg',
        ]);

        $image = (new ImageExtension())->image($asset, 0, 0);

        self::assertInstanceOf(Image::class, $image);
        self::assertSame('https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg', (string) $image);
    }

    #[Test]
    public function imageWithFocalPoint(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg',
            'focus' => '300x200:500x400',
        ]);

        $image = (new ImageExtension())->image($asset);

        self::assertInstanceOf(Image::class, $image);
        self::assertStringContainsString('filters:focal(300x200:500x400)', (string) $image);
    }

    #[Test]
    public function imageWithResizeAndFocalPoint(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg',
            'focus' => '300x200:500x400',
        ]);

        $image = (new ImageExtension())->image($asset, 640, 480);

        self::assertInstanceOf(Image::class, $image);
        self::assertStringContainsString('/640x480/', (string) $image);
        self::assertStringContainsString('filters:focal(300x200:500x400)', (string) $image);
    }
}
