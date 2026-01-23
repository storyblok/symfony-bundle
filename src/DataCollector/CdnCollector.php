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

use Storyblok\Bundle\Cdn\Storage\TraceableCdnFileStorage;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class CdnCollector extends AbstractDataCollector implements LateDataCollectorInterface
{
    public function __construct(
        private readonly TraceableCdnFileStorage $storage,
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->lateCollect();
    }

    public function lateCollect(): void
    {
        $traces = $this->storage->getTraces();

        $this->data['traces'] ??= [];
        $this->data['cached_count'] ??= 0;
        $this->data['pending_count'] ??= 0;

        foreach ($traces as $trace) {
            // Rename 'hit' to 'cached' for the template
            $trace['cached'] = $trace['hit'];
            unset($trace['hit']);

            if ($trace['cached']) {
                ++$this->data['cached_count'];
            } else {
                ++$this->data['pending_count'];
            }

            $this->data['traces'][] = $trace;
        }

        $this->storage->reset();
    }

    /**
     * @return list<array{
     *     id: string,
     *     filename: string,
     *     operation: string,
     *     cached: bool,
     *     originalUrl: null|string,
     * }>
     */
    public function getTraces(): array
    {
        return $this->data['traces'] ?? [];
    }

    public function getCachedCount(): int
    {
        return $this->data['cached_count'] ?? 0;
    }

    public function getPendingCount(): int
    {
        return $this->data['pending_count'] ?? 0;
    }

    public function getTotalCount(): int
    {
        return $this->getCachedCount() + $this->getPendingCount();
    }

    public function reset(): void
    {
        $this->data = [
            'traces' => [],
            'cached_count' => 0,
            'pending_count' => 0,
        ];
    }

    public static function getTemplate(): string
    {
        return '@Storyblok/cdn_collector.html.twig';
    }
}
