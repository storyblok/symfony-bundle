<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Controller;

use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Download\FileDownloaderInterface;
use Storyblok\Bundle\Cdn\Storage\CdnFileNotFoundException;
use Storyblok\Bundle\Cdn\Storage\CdnFileStorageInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CdnController
{
    public function __construct(
        private CdnFileStorageInterface $storage,
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
            $cdnFile = $this->storage->get($fileId, $fullFilename);
        } catch (CdnFileNotFoundException) {
            throw new NotFoundHttpException('Asset not found');
        }

        if (null === $cdnFile->file) {
            $downloaded = $this->downloader->download($cdnFile->metadata->originalUrl);
            $enrichedMetadata = $cdnFile->metadata->withDownloadInfo(
                $downloaded->metadata->contentType,
                $downloaded->metadata->etag,
                $downloaded->metadata->expiresAt,
            );
            $this->storage->set($fileId, $fullFilename, $enrichedMetadata, $downloaded->content);

            $cdnFile = $this->storage->get($fileId, $fullFilename);
        }

        $response = new BinaryFileResponse($cdnFile->file, contentDisposition: ResponseHeaderBag::DISPOSITION_INLINE);

        if (null !== $cdnFile->metadata->contentType) {
            $response->headers->set('Content-Type', $cdnFile->metadata->contentType);
        }

        if (null !== $cdnFile->metadata->etag) {
            $response->setEtag($cdnFile->metadata->etag);
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
