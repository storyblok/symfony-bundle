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
 * A single generated block property, derived from one Storyblok schema field.
 *
 * A property is either "mapped" (it has a {@see Type} and produces a typed property
 * plus a {@see \Storyblok\Bundle\Util\ValueObjectTrait} assignment) or "unmapped"
 * (the Storyblok field type has no clean representation, so only a `// @TODO`
 * assignment stub is generated for the developer to complete).
 *
 * @internal
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final readonly class Property
{
    public function __construct(
        /**
         * The original Storyblok field name (schema key), e.g. "hero_title".
         */
        public string $key,
        /**
         * The camelCase PHP property name, e.g. "heroTitle".
         */
        public string $name,
        public ?Type $type = null,
        public bool $nullable = false,
        public ?int $maxLength = null,
        public ?int $min = null,
        public ?int $max = null,
        /**
         * The raw Storyblok field type when it could not be mapped to a {@see Type}.
         */
        public ?string $storyblokType = null,
        /**
         * A backed enum generated from an "option" field's inline options.
         */
        public ?GeneratedEnum $enum = null,
        /**
         * The item class short name for a "bloks" field restricted to a single component,
         * e.g. "TextWithBulletsItem". Produces a typed list via self::list() instead of the
         * dynamic self::Blocks().
         */
        public ?string $itemClass = null,
    ) {
    }

    public function isEnum(): bool
    {
        return null !== $this->enum;
    }

    public function isUnmapped(): bool
    {
        return null === $this->type && null === $this->enum;
    }

    /**
     * The PHP type declaration for the property, e.g. "string", "?string", "array".
     */
    public function typehint(): string
    {
        if (null !== $this->enum) {
            return $this->nullable ? '?'.$this->enum->shortName : $this->enum->shortName;
        }

        if (null === $this->type) {
            return 'mixed';
        }

        $typehint = $this->type->typehint();

        if ($this->nullable && 'array' !== $typehint) {
            return '?'.$typehint;
        }

        return $typehint;
    }

    /**
     * The property declaration line, e.g. "public string $heroTitle;".
     */
    public function declaration(): string
    {
        return \sprintf('public %s $%s;', $this->typehint(), $this->name);
    }

    /**
     * A PHPDoc block for list-like properties, or null when none is needed.
     */
    public function phpDoc(): ?string
    {
        if (Type::Blocks === $this->type) {
            return \sprintf('/** @var list<%s> */', $this->itemClass ?? 'object');
        }

        return null;
    }

    /**
     * The full constructor assignment for a mapped property, e.g.
     * "$this->heroTitle = self::string($values, 'hero_title');".
     */
    public function assignment(): string
    {
        if (null !== $this->enum) {
            return \sprintf('$this->%s = %s;', $this->name, $this->enumExpression($this->enum));
        }

        if (Type::Blocks === $this->type && null !== $this->itemClass) {
            return \sprintf('$this->%s = %s;', $this->name, $this->listExpression($this->itemClass));
        }

        if (null === $this->type) {
            throw new \LogicException('Cannot build an assignment for an unmapped property.');
        }

        return \sprintf(
            '$this->%s = %s;',
            $this->name,
            $this->type->expression($this->key, $this->nullable, $this->maxLength, $this->min, $this->max),
        );
    }

    /**
     * The `// @TODO` assignment stub for an unmapped property.
     */
    public function todo(): string
    {
        return \sprintf(
            '// @TODO $this->%s = ...($values, \'%s\'); // unsupported Storyblok field type "%s"',
            $this->name,
            $this->key,
            $this->storyblokType ?? 'unknown',
        );
    }

    private function enumExpression(GeneratedEnum $enum): string
    {
        $key = \sprintf("'%s'", \str_replace("'", "\\'", $this->key));
        $call = \sprintf('self::enum($values, %s, %s::class)', $key, $enum->shortName);

        if (!$this->nullable) {
            return $call;
        }

        return \sprintf('\array_key_exists(%s, $values) && \'\' !== $values[%s] ? %s : null', $key, $key, $call);
    }

    private function listExpression(string $itemClass): string
    {
        $key = \sprintf("'%s'", \str_replace("'", "\\'", $this->key));
        $bounds = '';

        if (null !== $this->min || null !== $this->max) {
            $bounds = \sprintf(', %s, %s', $this->min ?? 'null', $this->max ?? 'null');
        }

        return \sprintf('self::list($values, %s, %s::class%s)', $key, $itemClass, $bounds);
    }
}
