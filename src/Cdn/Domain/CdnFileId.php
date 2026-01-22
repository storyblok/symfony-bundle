<?php

declare(strict_types=1);

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
