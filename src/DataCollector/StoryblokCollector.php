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

namespace Storyblok\Bundle\DataCollector;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Symfony\Component\VarDumper\Caster\ImgStub;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class StoryblokCollector extends AbstractDataCollector implements LateDataCollectorInterface
{
    use HttpClientTrait;

    public function __construct(
        private readonly TraceableHttpClient $client,
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->lateCollect();
    }

    public function lateCollect(): void
    {
        [$errorCount, $traces] = $this->collectOnClient($this->client);

        $this->data['request_count'] ??= 0;
        $this->data['error_count'] ??= 0;
        $this->data += ['traces' => []];

        $this->data['error_count'] += $errorCount;
        $this->data['request_count'] += \count($traces);
        $this->data['traces'] = array_merge($this->data['traces'], $traces);

        $this->client->reset();
    }

    /**
     * @return list<array{
     *     method: string,
     *     url: string,
     *     info: array<string, mixed>,
     * }>
     */
    public function getTraces(): array
    {
        return $this->data['traces'] ?? [];
    }

    public function getRequestCount(): int
    {
        return $this->data['request_count'] ?? 0;
    }

    public function getErrorCount(): int
    {
        return $this->data['error_count'] ?? 0;
    }

    public function reset(): void
    {
        $this->data = [
            'traces' => [],
            'request_count' => 0,
            'error_count' => 0,
        ];
    }

    public static function getTemplate(): string
    {
        return '@Storyblok/data_collector.html.twig';
    }

    private function collectOnClient(TraceableHttpClient $client): array
    {
        $traces = $client->getTracedRequests();

        $errorCount = 0;
        $baseInfo = [
            'response_headers' => 1,
            'retry_count' => 1,
            'redirect_count' => 1,
            'redirect_url' => 1,
            'user_data' => 1,
            'error' => 1,
            'url' => 1,
        ];

        foreach ($traces as $i => $trace) {
            if (400 <= ($trace['info']['http_code'] ?? 0)) {
                ++$errorCount;
            }

            $info = $trace['info'];
            $traces[$i]['http_code'] = $info['http_code'] ?? 0;

            unset($info['filetime'], $info['http_code'], $info['ssl_verify_result'], $info['content_type']);

            if (($info['http_method'] ?? null) === $trace['method']) {
                unset($info['http_method']);
            }

            if (($info['url'] ?? null) === $trace['url']) {
                unset($info['url']);
            }

            foreach ($info as $k => $v) {
                if (!$v || (is_numeric($v) && 0 > $v)) {
                    unset($info[$k]);
                }
            }

            if (\is_string($content = $trace['content'])) {
                $contentType = 'application/octet-stream';

                foreach ($info['response_headers'] ?? [] as $h) {
                    if (str_starts_with(strtolower($h), strtolower('content-type: '))) {
                        $contentType = substr($h, \strlen('content-type: '));

                        break;
                    }
                }

                if (str_starts_with($contentType, 'image/') && class_exists(ImgStub::class)) {
                    $content = new ImgStub($content, $contentType, '');
                } else {
                    $content = [$content];
                }

                $content = ['response_content' => $content];
            } elseif (\is_array($content)) {
                $content = ['response_json' => $content];
            } else {
                $content = [];
            }

            if (isset($info['retry_count'])) {
                $content['retries'] = $info['previous_info'];
                unset($info['previous_info']);
            }

            $debugInfo = array_diff_key($info, $baseInfo);
            $info = ['info' => $debugInfo] + array_diff_key($info, $debugInfo) + $content;
            unset($traces[$i]['info']); // break PHP reference used by TraceableHttpClient
            $traces[$i]['info'] = $this->cloneVar($info);
            $traces[$i]['options'] = $this->cloneVar($trace['options']);
        }

        return [$errorCount, $traces];
    }
}
