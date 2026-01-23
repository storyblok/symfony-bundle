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
use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Storyblok\Bundle\Cdn\Storage\CdnStorageInterface;
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
        $decorated = $this->createMock(CdnStorageInterface::class);
        $storage = new TraceableCdnFileStorage($decorated);

        self::assertEmpty($storage->getTraces());
    }

    #[Test]
    public function hasMetadataTracksCachedWhenTrue(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';

        $decorated = $this->createMock(CdnStorageInterface::class);
        $decorated->expects(self::once())
            ->method('hasMetadata')
            ->with($id, $filename)
            ->willReturn(true);

        $storage = new TraceableCdnFileStorage($decorated);

        self::assertTrue($storage->hasMetadata($id, $filename));
        self::assertCount(1, $storage->getTraces());

        $trace = $storage->getTraces()[0];
        self::assertTrue($trace['cached']);
        self::assertSame('hasMetadata', $trace['operation']);
    }

    #[Test]
    public function hasMetadataDoesNotTrackWhenFalse(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';

        $decorated = $this->createMock(CdnStorageInterface::class);
        $decorated->expects(self::once())
            ->method('hasMetadata')
            ->with($id, $filename)
            ->willReturn(false);

        $storage = new TraceableCdnFileStorage($decorated);

        self::assertFalse($storage->hasMetadata($id, $filename));
        self::assertEmpty($storage->getTraces());
    }

    #[Test]
    public function hasFileIsDelegatedToDecorated(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';

        $decorated = $this->createMock(CdnStorageInterface::class);
        $decorated->expects(self::once())
            ->method('hasFile')
            ->with($id, $filename)
            ->willReturn(true);

        $storage = new TraceableCdnFileStorage($decorated);

        self::assertTrue($storage->hasFile($id, $filename));
        self::assertEmpty($storage->getTraces());
    }

    #[Test]
    public function getMetadataIsDelegatedToDecorated(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';
        $originalUrl = 'https://a.storyblok.com/f/12345/image.jpg';
        $metadata = new CdnFileMetadata($originalUrl);

        $decorated = $this->createMock(CdnStorageInterface::class);
        $decorated->expects(self::once())
            ->method('getMetadata')
            ->with($id, $filename)
            ->willReturn($metadata);

        $storage = new TraceableCdnFileStorage($decorated);

        $result = $storage->getMetadata($id, $filename);

        self::assertSame($metadata, $result);
        self::assertEmpty($storage->getTraces());
    }

    #[Test]
    public function getFileIsDelegatedToDecorated(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';

        $tempFile = tempnam(sys_get_temp_dir(), 'cdn_test_');
        file_put_contents($tempFile, 'content');

        try {
            $file = new File($tempFile);

            $decorated = $this->createMock(CdnStorageInterface::class);
            $decorated->expects(self::once())
                ->method('getFile')
                ->with($id, $filename)
                ->willReturn($file);

            $storage = new TraceableCdnFileStorage($decorated);

            $result = $storage->getFile($id, $filename);

            self::assertSame($file, $result);
            self::assertEmpty($storage->getTraces());
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function setMetadataWithContentTypeDoesNotTrack(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
        );

        $decorated = $this->createMock(CdnStorageInterface::class);
        $decorated->expects(self::once())
            ->method('setMetadata')
            ->with($id, $filename, $metadata);

        $storage = new TraceableCdnFileStorage($decorated);

        $storage->setMetadata($id, $filename, $metadata);

        self::assertEmpty($storage->getTraces());
    }

    #[Test]
    public function setMetadataWithoutContentTypeTracksPending(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';
        $originalUrl = 'https://a.storyblok.com/f/12345/image.jpg';
        $metadata = new CdnFileMetadata($originalUrl);

        $decorated = $this->createMock(CdnStorageInterface::class);
        $decorated->expects(self::once())
            ->method('setMetadata')
            ->with($id, $filename, $metadata);

        $storage = new TraceableCdnFileStorage($decorated);

        $storage->setMetadata($id, $filename, $metadata);

        self::assertCount(1, $storage->getTraces());

        $trace = $storage->getTraces()[0];
        self::assertSame($id->value, $trace['id']);
        self::assertSame($filename, $trace['filename']);
        self::assertSame('setMetadata', $trace['operation']);
        self::assertFalse($trace['cached']);
        self::assertSame($originalUrl, $trace['originalUrl']);
    }

    #[Test]
    public function setFileIsDelegatedToDecorated(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';
        $content = 'file content';

        $decorated = $this->createMock(CdnStorageInterface::class);
        $decorated->expects(self::once())
            ->method('setFile')
            ->with($id, $filename, $content);

        $storage = new TraceableCdnFileStorage($decorated);

        $storage->setFile($id, $filename, $content);

        self::assertEmpty($storage->getTraces());
    }

    #[Test]
    public function removeIsDelegatedToDecorated(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';

        $decorated = $this->createMock(CdnStorageInterface::class);
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

        $decorated = $this->createMock(CdnStorageInterface::class);

        $storage = new TraceableCdnFileStorage($decorated);

        $storage->setMetadata($id, $filename, $metadata);

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

        $decorated = $this->createMock(CdnStorageInterface::class);
        $decorated->method('hasMetadata')->willReturn(true);

        $storage = new TraceableCdnFileStorage($decorated);

        // Two cache hits via hasMetadata()
        $storage->hasMetadata($id1, $filename);
        $storage->hasMetadata($id2, $filename);

        // One pending via setMetadata()
        $storage->setMetadata(self::generateFileId(), $filename, $metadata);

        self::assertCount(3, $storage->getTraces());
    }

    private static function generateFileId(): CdnFileId
    {
        return CdnFileId::generate('https://a.storyblok.com/f/'.self::faker()->randomNumber(5).'/'.self::faker()->word().'.jpg');
    }
}
