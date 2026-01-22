<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Cdn\Download;

use Storyblok\Bundle\Cdn\Domain\DownloadedFile;

interface FileDownloaderInterface
{
    public function download(string $url): DownloadedFile;
}
