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

namespace Storyblok\Bundle\Tests\Unit\Cdn\Storage;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Cdn\Domain\CdnFile;
use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Storyblok\Bundle\Cdn\Storage\CdnFileStorageInterface;
use Storyblok\Bundle\Cdn\Storage\TraceableCdnFileStorage;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Symfony\Component\HttpFoundation\File\File;
use function Safe\file_put_contents;
use function Safe\tempnam;
use function Safe\unlink;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class TraceableCdnFileStorageTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function defaults(): void
    {
        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $storage = new TraceableCdnFileStorage($decorated);

        self::assertEmpty($storage->getTraces());
    }

    #[Test]
    public function hasTracksHitWhenTrue(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';

        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $decorated->expects(self::once())
            ->method('has')
            ->with($id, $filename)
            ->willReturn(true);

        $storage = new TraceableCdnFileStorage($decorated);

        self::assertTrue($storage->has($id, $filename));
        self::assertCount(1, $storage->getTraces());

        $trace = $storage->getTraces()[0];
        self::assertTrue($trace['hit']);
        self::assertSame('has', $trace['operation']);
    }

    #[Test]
    public function hasDoesNotTrackWhenFalse(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';

        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $decorated->expects(self::once())
            ->method('has')
            ->with($id, $filename)
            ->willReturn(false);

        $storage = new TraceableCdnFileStorage($decorated);

        self::assertFalse($storage->has($id, $filename));
        self::assertEmpty($storage->getTraces());
    }

    #[Test]
    public function getTracksHitWhenFileExists(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';
        $originalUrl = 'https://a.storyblok.com/f/12345/image.jpg';

        $tempFile = tempnam(sys_get_temp_dir(), 'cdn_test_');
        file_put_contents($tempFile, 'content');

        try {
            $metadata = new CdnFileMetadata($originalUrl);
            $cdnFile = new CdnFile($metadata, new File($tempFile));

            $decorated = $this->createMock(CdnFileStorageInterface::class);
            $decorated->expects(self::once())
                ->method('get')
                ->with($id, $filename)
                ->willReturn($cdnFile);

            $storage = new TraceableCdnFileStorage($decorated);

            $result = $storage->get($id, $filename);

            self::assertSame($cdnFile, $result);
            self::assertCount(1, $storage->getTraces());

            $trace = $storage->getTraces()[0];
            self::assertSame($id->value, $trace['id']);
            self::assertSame($filename, $trace['filename']);
            self::assertSame('get', $trace['operation']);
            self::assertTrue($trace['hit']);
            self::assertSame($originalUrl, $trace['originalUrl']);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function getTracksMissWhenFileDoesNotExist(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';
        $originalUrl = 'https://a.storyblok.com/f/12345/image.jpg';

        $metadata = new CdnFileMetadata($originalUrl);
        $cdnFile = new CdnFile($metadata, null);

        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $decorated->expects(self::once())
            ->method('get')
            ->with($id, $filename)
            ->willReturn($cdnFile);

        $storage = new TraceableCdnFileStorage($decorated);

        $result = $storage->get($id, $filename);

        self::assertSame($cdnFile, $result);
        self::assertCount(1, $storage->getTraces());

        $trace = $storage->getTraces()[0];
        self::assertSame($id->value, $trace['id']);
        self::assertSame($filename, $trace['filename']);
        self::assertSame('get', $trace['operation']);
        self::assertFalse($trace['hit']);
        self::assertSame($originalUrl, $trace['originalUrl']);
    }

    #[Test]
    public function setWithContentDoesNotTrack(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';
        $metadata = new CdnFileMetadata('https://a.storyblok.com/f/12345/image.jpg');
        $content = 'file content';

        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $decorated->expects(self::once())
            ->method('set')
            ->with($id, $filename, $metadata, $content);

        $storage = new TraceableCdnFileStorage($decorated);

        $storage->set($id, $filename, $metadata, $content);

        self::assertEmpty($storage->getTraces());
    }

    #[Test]
    public function setWithoutContentTracksMiss(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';
        $originalUrl = 'https://a.storyblok.com/f/12345/image.jpg';
        $metadata = new CdnFileMetadata($originalUrl);

        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $decorated->expects(self::once())
            ->method('set')
            ->with($id, $filename, $metadata, null);

        $storage = new TraceableCdnFileStorage($decorated);

        $storage->set($id, $filename, $metadata, null);

        self::assertCount(1, $storage->getTraces());

        $trace = $storage->getTraces()[0];
        self::assertSame($id->value, $trace['id']);
        self::assertSame($filename, $trace['filename']);
        self::assertSame('set', $trace['operation']);
        self::assertFalse($trace['hit']);
        self::assertSame($originalUrl, $trace['originalUrl']);
    }

    #[Test]
    public function removeIsDelegatedToDecorated(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';

        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $decorated->expects(self::once())
            ->method('remove')
            ->with($id, $filename);

        $storage = new TraceableCdnFileStorage($decorated);

        $storage->remove($id, $filename);

        self::assertEmpty($storage->getTraces());
    }

    #[Test]
    public function reset(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';
        $metadata = new CdnFileMetadata('https://a.storyblok.com/f/12345/image.jpg');

        $decorated = $this->createMock(CdnFileStorageInterface::class);

        $storage = new TraceableCdnFileStorage($decorated);

        $storage->set($id, $filename, $metadata, null);

        self::assertCount(1, $storage->getTraces());

        $storage->reset();

        self::assertEmpty($storage->getTraces());
    }

    #[Test]
    public function multipleOperationsAreTracked(): void
    {
        $id1 = self::generateFileId();
        $id2 = self::generateFileId();
        $filename = 'image.jpg';
        $metadata = new CdnFileMetadata('https://a.storyblok.com/f/12345/image.jpg');

        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $decorated->method('has')->willReturn(true);

        $storage = new TraceableCdnFileStorage($decorated);

        // Two hits via has()
        $storage->has($id1, $filename);
        $storage->has($id2, $filename);

        // One miss via set()
        $storage->set(self::generateFileId(), $filename, $metadata, null);

        self::assertCount(3, $storage->getTraces());
    }

    private static function generateFileId(): CdnFileId
    {
        return CdnFileId::generate('https://a.storyblok.com/f/'.self::faker()->randomNumber(5).'/'.self::faker()->word().'.jpg');
    }
}
