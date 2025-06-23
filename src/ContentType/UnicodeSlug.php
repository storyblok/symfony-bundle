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

namespace Storyblok\Bundle\ContentType;

use OskarStark\Value\TrimmedNonEmptyString;
use function Symfony\Component\String\u;

/**
 * @internal
 *
 * We must URL encode the slug value to ensure it is safe for use in URLs. Storyblok slugs can contain special
 * characters, spaces, and other characters that may not be URL-safe.
 */
final readonly class UnicodeSlug implements \Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $value = TrimmedNonEmptyString::fromString($value)->toString();

        $urlEncodedValue = '';

        foreach (u($value)->split('/') as $key => $part) {
            if (0 < $key) {
                $urlEncodedValue .= '/';
            }

            $urlEncodedValue .= urlencode($part->toString());
        }

        $this->value = u($urlEncodedValue)
            ->trimEnd('/')
            ->trimStart('/')
            ->toString();
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->value;
    }
}
