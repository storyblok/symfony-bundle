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

namespace Storyblok\Bundle\Tests\Unit\Cdn\Download;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Safe\DateTimeImmutable;
use Storyblok\Bundle\Cdn\Domain\DownloadedFile;
use Storyblok\Bundle\Cdn\Download\AssetDownloader;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final class AssetDownloaderTest extends TestCase
{
    #[Test]
    public function downloadReturnsDownloadedFile(): void
    {
        $url = 'https://a.storyblok.com/f/12345/image.jpg';
        $content = 'binary image content';

        $response = new MockResponse($content, [
            'http_code' => 200,
            'response_headers' => [
                'content-type' => 'image/jpeg',
                'etag' => '"abc123"',
                'cache-control' => 'max-age=3600',
            ],
        ]);

        $client = new MockHttpClient($response);
        $downloader = new AssetDownloader($client);

        $result = $downloader->download($url);

        self::assertInstanceOf(DownloadedFile::class, $result);
        self::assertSame($content, $result->content);
        self::assertSame($url, $result->metadata->originalUrl);
        self::assertSame('image/jpeg', $result->metadata->contentType);
        self::assertSame('"abc123"', $result->metadata->etag);
        self::assertNotNull($result->metadata->expiresAt);
    }

    #[Test]
    public function downloadWithoutEtag(): void
    {
        $url = 'https://a.storyblok.com/f/12345/image.jpg';

        $response = new MockResponse('content', [
            'http_code' => 200,
            'response_headers' => [
                'content-type' => 'image/png',
                'cache-control' => 'max-age=7200',
            ],
        ]);

        $client = new MockHttpClient($response);
        $downloader = new AssetDownloader($client);

        $result = $downloader->download($url);

        self::assertSame('image/png', $result->metadata->contentType);
        self::assertNull($result->metadata->etag);
    }

    #[Test]
    public function downloadWithoutContentTypeUsesDefault(): void
    {
        $url = 'https://a.storyblok.com/f/12345/file.bin';

        $response = new MockResponse('binary content', [
            'http_code' => 200,
            'response_headers' => [
                'cache-control' => 'max-age=3600',
            ],
        ]);

        $client = new MockHttpClient($response);
        $downloader = new AssetDownloader($client);

        $result = $downloader->download($url);

        self::assertSame('application/octet-stream', $result->metadata->contentType);
    }

    #[Test]
    public function downloadParsesMaxAgeFromCacheControl(): void
    {
        $url = 'https://a.storyblok.com/f/12345/image.jpg';
        $beforeDownload = new DateTimeImmutable();

        $response = new MockResponse('content', [
            'http_code' => 200,
            'response_headers' => [
                'content-type' => 'image/jpeg',
                'cache-control' => 'public, max-age=7200, immutable',
            ],
        ]);

        $client = new MockHttpClient($response);
        $downloader = new AssetDownloader($client);

        $result = $downloader->download($url);

        self::assertNotNull($result->metadata->expiresAt);

        $expectedMinExpiry = $beforeDownload->modify('+7200 seconds');
        $expectedMaxExpiry = $beforeDownload->modify('+7210 seconds');

        self::assertGreaterThanOrEqual($expectedMinExpiry, $result->metadata->expiresAt);
        self::assertLessThanOrEqual($expectedMaxExpiry, $result->metadata->expiresAt);
    }

    #[Test]
    public function downloadUsesDefaultTtlWithoutMaxAge(): void
    {
        $url = 'https://a.storyblok.com/f/12345/image.jpg';
        $beforeDownload = new DateTimeImmutable();

        $response = new MockResponse('content', [
            'http_code' => 200,
            'response_headers' => [
                'content-type' => 'image/jpeg',
                'cache-control' => 'public, immutable',
            ],
        ]);

        $client = new MockHttpClient($response);
        $downloader = new AssetDownloader($client);

        $result = $downloader->download($url);

        self::assertNotNull($result->metadata->expiresAt);

        $expectedMinExpiry = $beforeDownload->modify('+86400 seconds');
        $expectedMaxExpiry = $beforeDownload->modify('+86410 seconds');

        self::assertGreaterThanOrEqual($expectedMinExpiry, $result->metadata->expiresAt);
        self::assertLessThanOrEqual($expectedMaxExpiry, $result->metadata->expiresAt);
    }

    #[Test]
    public function downloadUsesDefaultTtlWithoutCacheControlHeader(): void
    {
        $url = 'https://a.storyblok.com/f/12345/image.jpg';
        $beforeDownload = new DateTimeImmutable();

        $response = new MockResponse('content', [
            'http_code' => 200,
            'response_headers' => [
                'content-type' => 'image/jpeg',
            ],
        ]);

        $client = new MockHttpClient($response);
        $downloader = new AssetDownloader($client);

        $result = $downloader->download($url);

        self::assertNotNull($result->metadata->expiresAt);

        $expectedMinExpiry = $beforeDownload->modify('+86400 seconds');
        $expectedMaxExpiry = $beforeDownload->modify('+86410 seconds');

        self::assertGreaterThanOrEqual($expectedMinExpiry, $result->metadata->expiresAt);
        self::assertLessThanOrEqual($expectedMaxExpiry, $result->metadata->expiresAt);
    }

    #[Test]
    public function downloadMakesGetRequest(): void
    {
        $url = 'https://a.storyblok.com/f/12345/image.jpg';

        $response = new MockResponse('content', [
            'http_code' => 200,
            'response_headers' => [
                'content-type' => 'image/jpeg',
            ],
        ]);

        $client = new MockHttpClient($response);
        $downloader = new AssetDownloader($client);

        $downloader->download($url);

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame($url, $response->getRequestUrl());
    }
}
