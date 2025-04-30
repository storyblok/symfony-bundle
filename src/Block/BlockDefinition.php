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

namespace Storyblok\Bundle\Block;

use Webmozart\Assert\Assert;

final readonly class BlockDefinition
{
    /**
     * @param class-string $className
     */
    public function __construct(
        public string $name,
        public string $className,
        public string $template,
    ) {
        Assert::stringNotEmpty($name);
        Assert::notWhitespaceOnly($name);

        Assert::stringNotEmpty($className);
        Assert::notWhitespaceOnly($className);
        Assert::classExists($className);

        Assert::stringNotEmpty($template);
        Assert::notWhitespaceOnly($template);
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function fromArray(array $values): self
    {
        Assert::keyExists($values, 'className');
        Assert::string($values['className']);
        Assert::classExists($values['className']);

        Assert::keyExists($values, 'name');
        Assert::string($values['name']);

        Assert::keyExists($values, 'template');
        Assert::string($values['template']);

        return new self(
            name: $values['name'],
            className: $values['className'],
            template: $values['template'],
        );
    }
}
