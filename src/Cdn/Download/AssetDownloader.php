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

namespace Storyblok\Bundle\Cdn\Download;

use Safe\DateTimeImmutable;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Storyblok\Bundle\Cdn\Domain\DownloadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Symfony\Component\String\u;

final readonly class AssetDownloader implements FileDownloaderInterface
{
    private const int DEFAULT_TTL = 86400;

    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    public function download(string $url): DownloadedFile
    {
        $response = $this->client->request('GET', $url);
        $headers = $response->getHeaders();

        return new DownloadedFile(
            content: $response->getContent(),
            metadata: new CdnFileMetadata(
                originalUrl: $url,
                contentType: $headers['content-type'][0] ?? 'application/octet-stream',
                etag: $headers['etag'][0] ?? null,
                expiresAt: (new DateTimeImmutable())->modify(\sprintf('+%d seconds', self::parseTtl($headers))),
            ),
        );
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private static function parseTtl(array $headers): int
    {
        $cacheControl = u($headers['cache-control'][0] ?? '');

        foreach ($cacheControl->split(',') as $directive) {
            $directive = $directive->trim()->lower();

            if ($directive->startsWith('max-age=')) {
                return (int) $directive->after('=')->toString();
            }
        }

        return self::DEFAULT_TTL;
    }
}
