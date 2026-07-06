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

namespace Storyblok\Bundle\Tests\Unit\Maker\Storyblok;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Maker\Storyblok\ManagementApiComponentProvider;
use Storyblok\ManagementApi\Endpoints\ComponentApi;
use Storyblok\ManagementApi\ManagementApiClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use function Safe\json_encode;

final class ManagementApiComponentProviderTest extends TestCase
{
    #[Test]
    public function componentsAreMappedToRemoteComponents(): void
    {
        $payload = [
            'components' => [
                [
                    'name' => 'hero',
                    'schema' => [
                        'title' => ['type' => 'text', 'required' => true, 'pos' => 0],
                    ],
                ],
                [
                    'name' => 'teaser',
                    'schema' => [],
                ],
            ],
        ];

        $client = ManagementApiClient::initTest(new MockHttpClient([
            new MockResponse(json_encode($payload)),
        ]));

        $provider = new ManagementApiComponentProvider(new ComponentApi($client, 'space-id'), 'space-id', 'token');

        $components = $provider->components();

        self::assertCount(2, $components);
        self::assertSame('hero', $components[0]->name);
        self::assertArrayHasKey('title', $components[0]->schema);
        self::assertSame('teaser', $components[1]->name);
        self::assertSame([], $components[1]->schema);
    }

    #[Test]
    public function throwsWhenSpaceIdIsEmpty(): void
    {
        $client = ManagementApiClient::initTest(new MockHttpClient());
        $provider = new ManagementApiComponentProvider(new ComponentApi($client, ''), '', 'token');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('space id is empty');

        $provider->components();
    }

    #[Test]
    public function throwsWhenManagementTokenIsEmpty(): void
    {
        $client = ManagementApiClient::initTest(new MockHttpClient());
        $provider = new ManagementApiComponentProvider(new ComponentApi($client, 'space-id'), 'space-id', '');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Management API token is empty');

        $provider->components();
    }
}
