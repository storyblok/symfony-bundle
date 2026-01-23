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

namespace Storyblok\Bundle\Cdn\Storage;

use Storyblok\Bundle\Cdn\Domain\CdnFile;
use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Symfony\Contracts\Service\ResetInterface;

/**
 * A decorator for CdnFileStorageInterface that traces storage operations
 * for debugging and profiling purposes.
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class TraceableCdnFileStorage implements CdnFileStorageInterface, ResetInterface
{
    /**
     * @var list<array{
     *     id: string,
     *     filename: string,
     *     operation: string,
     *     hit: bool,
     *     originalUrl: null|string,
     * }>
     */
    private array $traces = [];

    public function __construct(
        private readonly CdnFileStorageInterface $decorated,
    ) {
    }

    public function has(CdnFileId $id, string $filename): bool
    {
        $result = $this->decorated->has($id, $filename);

        // Only track hits here - misses will be tracked via set()
        if ($result) {
            $this->traces[] = [
                'id' => $id->value,
                'filename' => $filename,
                'operation' => 'has',
                'hit' => true,
                'originalUrl' => null,
            ];
        }

        return $result;
    }

    public function get(CdnFileId $id, string $filename): CdnFile
    {
        $cdnFile = $this->decorated->get($id, $filename);

        // A "hit" means the file content was already cached (not just metadata)
        $hit = null !== $cdnFile->file;

        $this->traces[] = [
            'id' => $id->value,
            'filename' => $filename,
            'operation' => 'get',
            'hit' => $hit,
            'originalUrl' => $cdnFile->metadata->originalUrl,
        ];

        return $cdnFile;
    }

    public function set(CdnFileId $id, string $filename, CdnFileMetadata $metadata, ?string $content = null): void
    {
        $this->decorated->set($id, $filename, $metadata, $content);

        // Track set() with null content as a "miss" - this is a new CDN URL being prepared
        // The actual file will be downloaded lazily when the browser requests it
        if (null === $content) {
            $this->traces[] = [
                'id' => $id->value,
                'filename' => $filename,
                'operation' => 'set',
                'hit' => false,
                'originalUrl' => $metadata->originalUrl,
            ];
        }
    }

    public function remove(CdnFileId $id, string $filename): void
    {
        $this->decorated->remove($id, $filename);
    }

    /**
     * @return list<array{
     *     id: string,
     *     filename: string,
     *     operation: string,
     *     hit: bool,
     *     originalUrl: null|string,
     * }>
     */
    public function getTraces(): array
    {
        return $this->traces;
    }

    public function reset(): void
    {
        $this->traces = [];
    }
}
