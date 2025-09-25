<?php

namespace Storyblok\Bundle\Maker;

/**
 * @internal
 */
final class Property
{
    public function __construct(
        public ?string $name = null,
        public ?Type $type = null,
        public bool $nullable = false,
        public ?int $maxLength = null,
        public ?string $class = null,
        public ?int $min = null,
        public ?int $max = null,
    ) {
    }

    public function toString(): string
    {
        return sprintf(
            'public %s%s $%s;',
            $this->nullable ? '?' : '',
            $this->typehint(),
            $this->name,
        );
    }

    public function typehint(): ?string
    {
        return match ($this->type) {
            Type::Asset,
            Type::DateTimeImmutable,
            Type::Editable,
            Type::Link,
            Type::RichText,
            Type::Uuid,
            Type::MultiLink => $this->type->name,
            Type::String => 'string',
            Type::Float => 'float',
            Type::Integer => 'int',
            Type::Boolean => 'bool',
            Type::List,
            Type::Blocks => 'array',
            Type::Enum => $this->class,
        };
    }
}
