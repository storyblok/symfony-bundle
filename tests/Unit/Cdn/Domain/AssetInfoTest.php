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

namespace Storyblok\Bundle\Tests\Unit\Cdn\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Api\Domain\Type\Asset;
use Storyblok\Bundle\Cdn\Domain\AssetInfo;
use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\ImageService\Image;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final class AssetInfoTest extends TestCase
{
    #[Test]
    public function constructFromAssetWithDimensions(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg',
        ]);

        $assetInfo = new AssetInfo($asset);

        self::assertInstanceOf(CdnFileId::class, $assetInfo->id);
        self::assertSame('https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg', $assetInfo->url);
        self::assertSame('jpg', $assetInfo->extension);
        self::assertSame('1920x1080-image', $assetInfo->filename);
        self::assertSame('1920x1080-image.jpg', $assetInfo->fullFilename);
    }

    #[Test]
    public function constructFromAssetWithoutDimensions(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/document.pdf',
        ]);

        $assetInfo = new AssetInfo($asset);

        self::assertSame('document', $assetInfo->filename);
        self::assertSame('document.pdf', $assetInfo->fullFilename);
        self::assertSame('pdf', $assetInfo->extension);
    }

    #[Test]
    public function constructFromImage(): void
    {
        $image = (new Image('https://a.storyblok.com/f/12345/1920x1080/abc123/photo.webp'))
            ->resize(640, 480);

        $assetInfo = new AssetInfo($image);

        self::assertInstanceOf(CdnFileId::class, $assetInfo->id);
        self::assertSame('webp', $assetInfo->extension);
        self::assertSame('640x480-photo', $assetInfo->filename);
        self::assertSame('640x480-photo.webp', $assetInfo->fullFilename);
    }

    #[Test]
    public function constructFromImageWithoutResize(): void
    {
        $image = new Image('https://a.storyblok.com/f/12345/1920x1080/abc123/image.png');

        $assetInfo = new AssetInfo($image);

        self::assertSame('1920x1080-image', $assetInfo->filename);
        self::assertSame('1920x1080-image.png', $assetInfo->fullFilename);
        self::assertSame('png', $assetInfo->extension);
    }

    #[Test]
    public function idIsGeneratedFromUrl(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/test.jpg',
        ]);

        $assetInfo = new AssetInfo($asset);
        $expectedId = CdnFileId::generate('https://a.storyblok.com/f/12345/test.jpg');

        self::assertSame($expectedId->value, $assetInfo->id->value);
    }

    #[Test]
    public function sameAssetProducesSameId(): void
    {
        $asset1 = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/image.jpg',
        ]);

        $asset2 = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/image.jpg',
        ]);

        $assetInfo1 = new AssetInfo($asset1);
        $assetInfo2 = new AssetInfo($asset2);

        self::assertSame($assetInfo1->id->value, $assetInfo2->id->value);
    }

    #[Test]
    public function differentAssetsProduceDifferentIds(): void
    {
        $asset1 = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/image1.jpg',
        ]);

        $asset2 = new Asset([
            'id' => 12346,
            'filename' => 'https://a.storyblok.com/f/12345/image2.jpg',
        ]);

        $assetInfo1 = new AssetInfo($asset1);
        $assetInfo2 = new AssetInfo($asset2);

        self::assertNotSame($assetInfo1->id->value, $assetInfo2->id->value);
    }

    #[Test]
    public function resizedImageProducesDifferentIdThanOriginal(): void
    {
        $image = new Image('https://a.storyblok.com/f/12345/1920x1080/abc/photo.jpg');
        $resizedImage = $image->resize(640, 480);

        $assetInfo1 = new AssetInfo($image);
        $assetInfo2 = new AssetInfo($resizedImage);

        self::assertNotSame($assetInfo1->id->value, $assetInfo2->id->value);
        self::assertNotSame($assetInfo1->url, $assetInfo2->url);
    }

    #[Test]
    public function extensionIsAlwaysLowercase(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.JPG',
        ]);

        $assetInfo = new AssetInfo($asset);

        self::assertSame('jpg', $assetInfo->extension);
        self::assertSame('1920x1080-image.jpg', $assetInfo->fullFilename);
    }
}
