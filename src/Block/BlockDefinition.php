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

namespace Storyblok\Bundle\Block;

use Webmozart\Assert\Assert;

final readonly class BlockDefinition
{
    /**
     * @param class-string $className
     */
    public function __construct(
        public string $technicalName,
        public string $className,
        public string $template,
    ) {
        Assert::stringNotEmpty($technicalName);
        Assert::notWhitespaceOnly($technicalName);

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

        Assert::keyExists($values, 'technicalName');
        Assert::string($values['technicalName']);

        Assert::keyExists($values, 'template');
        Assert::string($values['template']);

        return new self(
            technicalName: $values['technicalName'],
            className: $values['className'],
            template: $values['template'],
        );
    }
}
