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

namespace Storyblok\Bundle\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Safe\DateTimeImmutable;
use Storyblok\Bundle\Command\CdnCleanupCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use function Safe\json_encode;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final class CdnCleanupCommandTest extends TestCase
{
    private string $storagePath;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir().'/cdn_cleanup_test_'.bin2hex(random_bytes(8));
        $this->filesystem = new Filesystem();
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->storagePath)) {
            $this->filesystem->remove($this->storagePath);
        }
    }

    #[Test]
    public function executeWithNoStorageDirectory(): void
    {
        $command = new CdnCleanupCommand($this->filesystem, $this->storagePath);
        $tester = new CommandTester($command);

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('does not exist', $tester->getDisplay());
    }

    #[Test]
    public function executeWithEmptyStorageDirectory(): void
    {
        $this->filesystem->mkdir($this->storagePath);

        $command = new CdnCleanupCommand($this->filesystem, $this->storagePath);
        $tester = new CommandTester($command);

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('No cached files found', $tester->getDisplay());
    }

    #[Test]
    public function executeDeletesAllDirectories(): void
    {
        $this->filesystem->mkdir($this->storagePath.'/abc123');
        $this->filesystem->mkdir($this->storagePath.'/def456');
        $this->filesystem->dumpFile($this->storagePath.'/abc123/image.jpg', 'content');
        $this->filesystem->dumpFile($this->storagePath.'/def456/photo.png', 'content');

        $command = new CdnCleanupCommand($this->filesystem, $this->storagePath);
        $tester = new CommandTester($command);

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('2 directories deleted', $tester->getDisplay());
        self::assertFalse($this->filesystem->exists($this->storagePath.'/abc123'));
        self::assertFalse($this->filesystem->exists($this->storagePath.'/def456'));
    }

    #[Test]
    public function executeWithDryRunDoesNotDelete(): void
    {
        $this->filesystem->mkdir($this->storagePath.'/abc123');
        $this->filesystem->dumpFile($this->storagePath.'/abc123/image.jpg', 'content');

        $command = new CdnCleanupCommand($this->filesystem, $this->storagePath);
        $tester = new CommandTester($command);

        $tester->execute(['--dry-run' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Would delete', $tester->getDisplay());
        self::assertStringContainsString('Dry-run', $tester->getDisplay());
        self::assertTrue($this->filesystem->exists($this->storagePath.'/abc123'));
    }

    #[Test]
    public function executeWithExpiredOptionDeletesOnlyExpired(): void
    {
        $expiredMetadata = [
            'originalUrl' => 'https://example.com/expired.jpg',
            'contentType' => 'image/jpeg',
            'expiresAt' => (new DateTimeImmutable('-1 day'))->format(\DateTimeInterface::ATOM),
        ];

        $validMetadata = [
            'originalUrl' => 'https://example.com/valid.jpg',
            'contentType' => 'image/jpeg',
            'expiresAt' => (new DateTimeImmutable('+1 day'))->format(\DateTimeInterface::ATOM),
        ];

        $this->filesystem->mkdir($this->storagePath.'/expired123');
        $this->filesystem->mkdir($this->storagePath.'/valid456');
        $this->filesystem->dumpFile($this->storagePath.'/expired123/image.jpg.json', json_encode($expiredMetadata));
        $this->filesystem->dumpFile($this->storagePath.'/valid456/image.jpg.json', json_encode($validMetadata));

        $command = new CdnCleanupCommand($this->filesystem, $this->storagePath);
        $tester = new CommandTester($command);

        $tester->execute(['--expired' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('1 directories deleted, 1 skipped', $tester->getDisplay());
        self::assertFalse($this->filesystem->exists($this->storagePath.'/expired123'));
        self::assertTrue($this->filesystem->exists($this->storagePath.'/valid456'));
    }

    #[Test]
    public function executeWithExpiredOptionSkipsDirectoriesWithoutExpiresAt(): void
    {
        $metadataWithoutExpiry = [
            'originalUrl' => 'https://example.com/image.jpg',
            'contentType' => 'image/jpeg',
        ];

        $this->filesystem->mkdir($this->storagePath.'/noexpiry');
        $this->filesystem->dumpFile($this->storagePath.'/noexpiry/image.jpg.json', json_encode($metadataWithoutExpiry));

        $command = new CdnCleanupCommand($this->filesystem, $this->storagePath);
        $tester = new CommandTester($command);

        $tester->execute(['--expired' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('0 directories deleted, 1 skipped', $tester->getDisplay());
        self::assertTrue($this->filesystem->exists($this->storagePath.'/noexpiry'));
    }
}
