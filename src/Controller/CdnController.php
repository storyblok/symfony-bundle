<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Controller;

use Storyblok\Bundle\Cdn\Domain\CdnFileId;
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
        private ?int $maxAge,
        private ?int $smaxAge,
        private ?bool $public,
    ) {
    }

    public function __invoke(string $id, string $filename, string $extension): Response
    {
        $fullFilename = \sprintf('%s.%s', $filename, $extension);

        try {
            $cdnFile = $this->storage->get(new CdnFileId($id), $fullFilename);
        } catch (CdnFileNotFoundException) {
            throw new NotFoundHttpException('File not found');
        }

        $response = new BinaryFileResponse(
            $cdnFile->file,
            contentDisposition: ResponseHeaderBag::DISPOSITION_INLINE,
        );

        $response->headers->set('Content-Type', $cdnFile->metadata->contentType);

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
