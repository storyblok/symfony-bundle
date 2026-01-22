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

namespace Storyblok\Bundle\Cdn\Domain;

use Webmozart\Assert\Assert;

final readonly class CdnFileId
{
    public function __construct(
        public string $value,
    ) {
        Assert::stringNotEmpty($value);
        Assert::notWhitespaceOnly($value);
        Assert::regex($value, '/[a-f0-9]{16}/');
    }

    public static function generate(string $url): self
    {
        return new self(\hash('xxh3', $url));
    }
}
