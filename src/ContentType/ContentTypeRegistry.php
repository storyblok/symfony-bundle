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

use Webmozart\Assert\Assert;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final readonly class ContentTypeRegistry implements ContentTypeRegistryInterface
{
    /**
     * @var list<class-string<ContentTypeInterface>>
     */
    private array $contentTypes;

    public function __construct(
        private ContentTypeControllerRegistry $controllerRegistry,
    ) {
        $contentTypes = \array_values(\array_map(
            static fn (ContentTypeControllerDefinition $definition) => $definition->contentType,
            $this->controllerRegistry->all(),
        ));

        $contentTypes = \array_values(\array_combine($contentTypes, $contentTypes));

        Assert::allIsAOf($contentTypes, ContentTypeInterface::class);

        $this->contentTypes = $contentTypes;
    }

    public function all(): array
    {
        return $this->contentTypes;
    }

    public function has(string $class): bool
    {
        return [] !== \array_filter($this->contentTypes, static fn (string $existingClass) => $existingClass === $class);
    }
}
