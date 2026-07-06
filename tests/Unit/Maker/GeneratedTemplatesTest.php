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

namespace Storyblok\Bundle\Tests\Unit\Maker;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Block\Attribute\AsBlock;
use Storyblok\Bundle\Maker\GeneratedEnum;
use Storyblok\Bundle\Maker\Property;
use Storyblok\Bundle\Maker\Type;
use Storyblok\Bundle\Util\ValueObjectTrait;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;
use function Safe\ob_get_clean;
use function Safe\ob_start;

final class GeneratedTemplatesTest extends TestCase
{
    #[Test]
    public function rendersBlockClass(): void
    {
        $classData = ClassData::create(
            class: 'App\\Block\\Hero',
            suffix: '',
            useStatements: [AsBlock::class, ValueObjectTrait::class],
        );
        $classData->setIsFinal(true);

        $output = self::renderBlock(
            $classData,
            [
                new Property(key: 'title', name: 'title', type: Type::String),
                new Property(key: 'subtitle', name: 'subtitle', type: Type::String, nullable: true),
                new Property(key: 'body', name: 'body', type: Type::Blocks),
                new Property(key: 'items', name: 'items', type: Type::Blocks, min: 1, max: 3, itemClass: 'CardRowItem'),
                new Property(key: 'style', name: 'style', enum: new GeneratedEnum('HeroStyle', ['Bold' => 'bold'])),
                new Property(key: 'seo', name: 'seo', storyblokType: 'custom'),
            ],
            'hero',
            'blocks/hero.html.twig',
        );

        self::assertValidPhp($output);
        self::assertStringContainsString('namespace App\Block;', $output);
        self::assertStringContainsString("#[AsBlock(name: 'hero', template: 'blocks/hero.html.twig')]", $output);
        self::assertStringContainsString('final readonly class Hero', $output);
        self::assertStringContainsString('use ValueObjectTrait;', $output);

        self::assertStringContainsString('public string $title;', $output);
        self::assertStringContainsString('public ?string $subtitle;', $output);
        self::assertStringContainsString('/** @var list<object> */', $output);
        self::assertStringContainsString('public array $body;', $output);
        self::assertStringContainsString('/** @var list<CardRowItem> */', $output);
        self::assertStringContainsString('public HeroStyle $style;', $output);

        self::assertStringContainsString("\$this->title = self::string(\$values, 'title');", $output);
        self::assertStringContainsString("\$this->subtitle = self::nullOrString(\$values, 'subtitle');", $output);
        self::assertStringContainsString("\$this->body = self::Blocks(\$values, 'body');", $output);
        self::assertStringContainsString("\$this->items = self::list(\$values, 'items', CardRowItem::class, 1, 3);", $output);
        self::assertStringContainsString("\$this->style = self::enum(\$values, 'style', HeroStyle::class);", $output);
        self::assertStringContainsString('// @TODO $this->seo = ...($values, \'seo\'); // unsupported Storyblok field type "custom"', $output);
    }

    #[Test]
    public function rendersEnum(): void
    {
        $output = self::renderEnum('App\\Block\\Enum', 'HeroStyle', ['Bold' => 'bold', 'Light' => 'light']);

        self::assertValidPhp($output);
        self::assertStringContainsString('namespace App\Block\Enum;', $output);
        self::assertStringContainsString('enum HeroStyle: string', $output);
        self::assertStringContainsString("case Bold = 'bold';", $output);
        self::assertStringContainsString("case Light = 'light';", $output);
    }

    #[Test]
    public function enumTemplateEscapesValues(): void
    {
        $output = self::renderEnum('App\\Block\\Enum', 'Weird', ['Quote' => "a'b"]);

        self::assertValidPhp($output);
        self::assertStringContainsString("case Quote = 'a\\'b';", $output);
    }

    #[Test]
    public function rendersTwigTemplate(): void
    {
        $output = self::renderTwig('App\\Block\\Hero');

        self::assertStringContainsString('{# @var block \App\Block\Hero #}', $output);
        self::assertStringContainsString('<div {{ block|storyblok_attributes }}>', $output);
    }

    private static function assertValidPhp(string $code): void
    {
        try {
            $tokens = \token_get_all($code, \TOKEN_PARSE);
        } catch (\ParseError $e) {
            self::fail(\sprintf('Generated code is not valid PHP: %s', $e->getMessage()));
        }

        self::assertNotSame([], $tokens);
        self::assertStringStartsWith('<?php', $code);
    }

    /**
     * @param list<Property> $properties
     */
    private static function renderBlock(ClassData $class_data, array $properties, string $block_name, string $block_template): string
    {
        ob_start();

        include \dirname(__DIR__, 3).'/src/Maker/templates/Block.tpl.php';

        return ob_get_clean();
    }

    /**
     * @param array<string, string> $cases
     */
    private static function renderEnum(string $namespace, string $class_name, array $cases): string
    {
        ob_start();

        include \dirname(__DIR__, 3).'/src/Maker/templates/Enum.tpl.php';

        return ob_get_clean();
    }

    private static function renderTwig(string $block_fqcn): string
    {
        ob_start();

        include \dirname(__DIR__, 3).'/src/Maker/templates/block_template.tpl.php';

        return ob_get_clean();
    }
}
