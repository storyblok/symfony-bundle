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

use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\Service\ResetInterface;

/**
 * A decorator for CdnStorageInterface that traces storage operations
 * for debugging and profiling purposes.
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class TraceableCdnStorage implements CdnStorageInterface, ResetInterface
{
    /**
     * @var list<array{
     *     id: string,
     *     filename: string,
     *     operation: string,
     *     cached: bool,
     *     originalUrl: null|string,
     * }>
     */
    private array $traces = [];

    public function __construct(
        private readonly CdnStorageInterface $decorated,
    ) {
    }

    public function hasMetadata(CdnFileId $id, string $filename): bool
    {
        $result = $this->decorated->hasMetadata($id, $filename);

        // Track cache hits (metadata exists = asset was previously processed)
        if ($result) {
            $this->traces[] = [
                'id' => $id->value,
                'filename' => $filename,
                'operation' => 'hasMetadata',
                'cached' => true,
                'originalUrl' => null,
            ];
        }

        return $result;
    }

    public function hasFile(CdnFileId $id, string $filename): bool
    {
        return $this->decorated->hasFile($id, $filename);
    }

    public function getMetadata(CdnFileId $id, string $filename): CdnFileMetadata
    {
        return $this->decorated->getMetadata($id, $filename);
    }

    public function getFile(CdnFileId $id, string $filename): File
    {
        return $this->decorated->getFile($id, $filename);
    }

    public function setMetadata(CdnFileId $id, string $filename, CdnFileMetadata $metadata): void
    {
        $this->decorated->setMetadata($id, $filename, $metadata);

        // Track pending assets (new metadata = new asset being prepared for lazy download)
        // Only track when contentType is null (initial creation, not enrichment after download)
        if (null === $metadata->contentType) {
            $this->traces[] = [
                'id' => $id->value,
                'filename' => $filename,
                'operation' => 'setMetadata',
                'cached' => false,
                'originalUrl' => $metadata->originalUrl,
            ];
        }
    }

    public function setFile(CdnFileId $id, string $filename, string $content): void
    {
        $this->decorated->setFile($id, $filename, $content);
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
     *     cached: bool,
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
