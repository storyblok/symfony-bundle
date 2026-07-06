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

namespace Storyblok\Bundle\Maker;

/**
 * A Storyblok component (block) fetched from the Management API, reduced to what the
 * maker needs: its technical name and its raw field schema.
 *
 * @internal
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final readonly class RemoteComponent
{
    /**
     * @param array<string, array<mixed>> $schema
     */
    public function __construct(
        public string $name,
        public array $schema = [],
    ) {
    }
}
