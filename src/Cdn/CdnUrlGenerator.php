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

namespace Storyblok\Bundle\Cdn;

use Storyblok\Api\Domain\Type\Asset;
use Storyblok\Bundle\Cdn\Domain\AssetInfo;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Storyblok\Bundle\Cdn\Storage\CdnFileStorageInterface;
use Storyblok\Bundle\Routing\Route;
use Storyblok\ImageService\Image;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final readonly class CdnUrlGenerator implements CdnUrlGeneratorInterface
{
    public function __construct(
        private CdnFileStorageInterface $storage,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function generate(Asset|Image $asset): string
    {
        $assetInfo = new AssetInfo($asset);

        if (!$this->storage->has($assetInfo->id, $assetInfo->fullFilename)) {
            $this->storage->set($assetInfo->id, $assetInfo->fullFilename, new CdnFileMetadata(originalUrl: $assetInfo->url));
        }

        return $this->urlGenerator->generate(Route::CDN, [
            'id' => $assetInfo->id->value,
            'filename' => $assetInfo->filename,
            'extension' => $assetInfo->extension,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
