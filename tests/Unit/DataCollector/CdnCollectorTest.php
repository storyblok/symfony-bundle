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

namespace Storyblok\Bundle\Tests\Unit\DataCollector;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Storyblok\Bundle\Cdn\Storage\CdnFileStorageInterface;
use Storyblok\Bundle\Cdn\Storage\TraceableCdnFileStorage;
use Storyblok\Bundle\DataCollector\CdnCollector;
use Storyblok\Bundle\Tests\Util\FakerTrait;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class CdnCollectorTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function defaults(): void
    {
        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $storage = new TraceableCdnFileStorage($decorated);
        $collector = new CdnCollector($storage);

        self::assertEmpty($collector->getTraces());
        self::assertSame(0, $collector->getCachedCount());
        self::assertSame(0, $collector->getPendingCount());
        self::assertSame(0, $collector->getTotalCount());
    }

    #[Test]
    public function getTemplate(): void
    {
        self::assertSame('@Storyblok/cdn_collector.html.twig', CdnCollector::getTemplate());
    }

    #[Test]
    public function lateCollectWithCached(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';

        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $decorated->method('has')->willReturn(true);

        $storage = new TraceableCdnFileStorage($decorated);
        $collector = new CdnCollector($storage);

        // Simulate cdn_url() call where file is already cached
        $storage->has($id, $filename);

        $collector->lateCollect();

        self::assertCount(1, $collector->getTraces());
        self::assertSame(1, $collector->getCachedCount());
        self::assertSame(0, $collector->getPendingCount());
        self::assertSame(1, $collector->getTotalCount());
    }

    #[Test]
    public function lateCollectWithPending(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';
        $metadata = new CdnFileMetadata('https://a.storyblok.com/f/12345/image.jpg');

        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $decorated->method('has')->willReturn(false);

        $storage = new TraceableCdnFileStorage($decorated);
        $collector = new CdnCollector($storage);

        // Simulate cdn_url() call where file doesn't exist yet
        $storage->has($id, $filename);
        $storage->set($id, $filename, $metadata, null);

        $collector->lateCollect();

        self::assertCount(1, $collector->getTraces());
        self::assertSame(0, $collector->getCachedCount());
        self::assertSame(1, $collector->getPendingCount());
        self::assertSame(1, $collector->getTotalCount());
    }

    #[Test]
    public function lateCollectWithMixedCachedAndPending(): void
    {
        $metadata = new CdnFileMetadata('https://a.storyblok.com/f/12345/image.jpg');

        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $decorated->method('has')
            ->willReturnOnConsecutiveCalls(true, false, true, false);

        $storage = new TraceableCdnFileStorage($decorated);
        $collector = new CdnCollector($storage);

        // 2 cached (has returns true), 2 pending (set with null content)
        $storage->has(self::generateFileId(), 'image1.jpg');
        $storage->has(self::generateFileId(), 'image2.jpg');
        $storage->set(self::generateFileId(), 'image2.jpg', $metadata, null);
        $storage->has(self::generateFileId(), 'image3.jpg');
        $storage->has(self::generateFileId(), 'image4.jpg');
        $storage->set(self::generateFileId(), 'image4.jpg', $metadata, null);

        $collector->lateCollect();

        self::assertCount(4, $collector->getTraces());
        self::assertSame(2, $collector->getCachedCount());
        self::assertSame(2, $collector->getPendingCount());
        self::assertSame(4, $collector->getTotalCount());
    }

    #[Test]
    public function reset(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';
        $metadata = new CdnFileMetadata('https://a.storyblok.com/f/12345/image.jpg');

        $decorated = $this->createMock(CdnFileStorageInterface::class);

        $storage = new TraceableCdnFileStorage($decorated);
        $collector = new CdnCollector($storage);

        $storage->set($id, $filename, $metadata, null);

        $collector->lateCollect();

        self::assertCount(1, $collector->getTraces());
        self::assertSame(1, $collector->getPendingCount());

        $collector->reset();

        self::assertEmpty($collector->getTraces());
        self::assertSame(0, $collector->getCachedCount());
        self::assertSame(0, $collector->getPendingCount());
        self::assertSame(0, $collector->getTotalCount());
    }

    #[Test]
    public function storageIsResetAfterLateCollect(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';
        $metadata = new CdnFileMetadata('https://a.storyblok.com/f/12345/image.jpg');

        $decorated = $this->createMock(CdnFileStorageInterface::class);

        $storage = new TraceableCdnFileStorage($decorated);
        $collector = new CdnCollector($storage);

        $storage->set($id, $filename, $metadata, null);

        self::assertCount(1, $storage->getTraces());

        $collector->lateCollect();

        self::assertEmpty($storage->getTraces());
    }

    #[Test]
    public function tracesContainCachedFlag(): void
    {
        $id = self::generateFileId();
        $filename = 'image.jpg';

        $decorated = $this->createMock(CdnFileStorageInterface::class);
        $decorated->method('has')->willReturn(true);

        $storage = new TraceableCdnFileStorage($decorated);
        $collector = new CdnCollector($storage);

        $storage->has($id, $filename);

        $collector->lateCollect();

        $traces = $collector->getTraces();
        self::assertCount(1, $traces);
        self::assertArrayHasKey('cached', $traces[0]);
        self::assertTrue($traces[0]['cached']);
    }

    private static function generateFileId(): CdnFileId
    {
        return CdnFileId::generate('https://a.storyblok.com/f/'.self::faker()->randomNumber(5).'/'.self::faker()->word().'.jpg');
    }
}
