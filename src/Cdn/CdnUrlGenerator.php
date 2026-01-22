<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Cdn;

use Storyblok\Api\Domain\Type\Asset;
use Storyblok\Bundle\Cdn\Domain\AssetInfo;
use Storyblok\Bundle\Cdn\Download\FileDownloaderInterface;
use Storyblok\Bundle\Cdn\Storage\CdnFileStorageInterface;
use Storyblok\Bundle\Routing\Route;
use Storyblok\ImageService\Image;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class CdnUrlGenerator implements CdnUrlGeneratorInterface
{
    public function __construct(
        private CdnFileStorageInterface $storage,
        private FileDownloaderInterface $downloader,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function generate(Asset|Image $asset): string
    {
        $assetInfo = new AssetInfo($asset);

        if (!$this->storage->has($assetInfo->id, $assetInfo->fullFilename)) {
            $downloaded = $this->downloader->download($assetInfo->url);
            $this->storage->set($assetInfo->id, $assetInfo->fullFilename, $downloaded->content, $downloaded->metadata);
        }

        return $this->urlGenerator->generate(Route::CDN, [
            'id' => $assetInfo->id->value,
            'filename' => $assetInfo->filename,
            'extension' => $assetInfo->extension,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
