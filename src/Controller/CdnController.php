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

namespace Storyblok\Bundle\Controller;

use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Download\FileDownloaderInterface;
use Storyblok\Bundle\Cdn\Storage\CdnFileNotFoundException;
use Storyblok\Bundle\Cdn\Storage\CdnStorageInterface;
use Storyblok\Bundle\Cdn\Storage\MetadataNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final readonly class CdnController
{
    public function __construct(
        private CdnStorageInterface $storage,
        private FileDownloaderInterface $downloader,
        private ?int $maxAge,
        private ?int $smaxAge,
        private ?bool $public,
    ) {
    }

    public function __invoke(string $id, string $filename, string $extension): Response
    {
        $fileId = new CdnFileId($id);
        $fullFilename = \sprintf('%s.%s', $filename, $extension);

        try {
            $metadata = $this->storage->getMetadata($fileId, $fullFilename);
        } catch (MetadataNotFoundException) {
            throw new NotFoundHttpException('Asset not found');
        }

        if (!$this->storage->hasFile($fileId, $fullFilename)) {
            $downloaded = $this->downloader->download($metadata->originalUrl);

            if (null === $downloaded->metadata->contentType || null === $downloaded->metadata->expiresAt) {
                throw new \RuntimeException('Downloaded file metadata is incomplete');
            }

            $metadata = $metadata->withDownloadInfo(
                $downloaded->metadata->contentType,
                $downloaded->metadata->etag,
                $downloaded->metadata->expiresAt,
            );
            $this->storage->setMetadata($fileId, $fullFilename, $metadata);
            $this->storage->setFile($fileId, $fullFilename, $downloaded->content);
        }

        try {
            $file = $this->storage->getFile($fileId, $fullFilename);
        } catch (CdnFileNotFoundException) {
            throw new NotFoundHttpException('Asset not found');
        }

        $response = new BinaryFileResponse($file, contentDisposition: ResponseHeaderBag::DISPOSITION_INLINE);

        if (null !== $metadata->contentType) {
            $response->headers->set('Content-Type', $metadata->contentType);
        }

        if (null !== $metadata->etag) {
            $response->setEtag($metadata->etag);
        }

        if (null !== $this->maxAge) {
            $response->setMaxAge($this->maxAge);
        }

        if (null !== $this->smaxAge) {
            $response->setSharedMaxAge($this->smaxAge);
        }

        if (null !== $this->public) {
            if ($this->public) {
                $response->setPublic();
            } else {
                $response->setPrivate();
            }
        }

        return $response;
    }
}
