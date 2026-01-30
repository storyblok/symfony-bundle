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
use Storyblok\Bundle\Cdn\CdnUrlGeneratorInterface;
use Storyblok\Bundle\Twig\CdnExtension;
use Storyblok\ImageService\Image;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final class CdnExtensionTest extends TestCase
{
    #[Test]
    public function getFunctions(): void
    {
        $urlGenerator = self::createMock(CdnUrlGeneratorInterface::class);

        $functions = (new CdnExtension($urlGenerator))->getFunctions();

        self::assertCount(1, $functions);
        self::assertSame('cdn_url', $functions[0]->getName());
    }

    #[Test]
    public function cdnUrlFunctionDelegatesToUrlGenerator(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg',
        ]);

        $urlGenerator = self::createMock(CdnUrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with($asset)
            ->willReturn('https://example.com/f/abc123/1920x1080-image.jpg');

        $extension = new CdnExtension($urlGenerator);
        $functions = $extension->getFunctions();

        $callable = $functions[0]->getCallable();
        self::assertIsCallable($callable);
        $result = $callable($asset);

        self::assertSame('https://example.com/f/abc123/1920x1080-image.jpg', $result);
    }

    #[Test]
    public function cdnUrlFunctionWorksWithImage(): void
    {
        $image = new Image('https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg');

        $urlGenerator = self::createMock(CdnUrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with($image)
            ->willReturn('https://example.com/f/def456/1920x1080-image.jpg');

        $extension = new CdnExtension($urlGenerator);
        $functions = $extension->getFunctions();

        $callable = $functions[0]->getCallable();
        self::assertIsCallable($callable);
        $result = $callable($image);

        self::assertSame('https://example.com/f/def456/1920x1080-image.jpg', $result);
    }
}
