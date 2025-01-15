<?php

declare(strict_types=1);

/**
 * This file is part of sensiolabs-de/storyblok-bundle.
 *
 * (c) SensioLabs Deutschland <info@sensiolabs.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Storyblok\Bundle\Tests\Unit\DataCollector;

use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\DataCollector\StoryblokCollector;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\TraceableHttpClient;

final class StoryblokCollectorTest extends TestCase
{
    use FakerTrait;

    /**
     * @test
     */
    public function defaults(): void
    {
        $client = new TraceableHttpClient(new MockHttpClient());
        $collector = new StoryblokCollector($client);

        self::assertEmpty($collector->getTraces());
        self::assertSame(0, $collector->getRequestCount());
        self::assertSame(0, $collector->getErrorCount());
    }

    /**
     * @test
     */
    public function getTemplate(): void
    {
        self::assertSame('@Storyblok/data_collector.html.twig', StoryblokCollector::getTemplate());
    }

    /**
     * @test
     */
    public function lateCollect(): void
    {
        $client = new TraceableHttpClient(new MockHttpClient([
            new JsonMockResponse(['hello' => 'there'], ['http_code' => 200]),
        ]));

        $client->request('GET', 'https://example.com');

        $collector = new StoryblokCollector($client);

        $collector->lateCollect();

        self::assertCount(1, $collector->getTraces());
        self::assertSame(1, $collector->getRequestCount());
        self::assertSame(0, $collector->getErrorCount());
    }

    /**
     * @test
     */
    public function reset(): void
    {
        $client = new TraceableHttpClient(new MockHttpClient([
            new JsonMockResponse(['hello' => 'there'], ['http_code' => 200]),
        ]));

        $client->request('GET', 'https://example.com');

        $collector = new StoryblokCollector($client);

        $collector->lateCollect();

        self::assertCount(1, $collector->getTraces());
        self::assertSame(1, $collector->getRequestCount());
        self::assertSame(0, $collector->getErrorCount());

        $collector->reset();

        self::assertEmpty($collector->getTraces());
        self::assertSame(0, $collector->getRequestCount());
        self::assertSame(0, $collector->getErrorCount());
    }
}
