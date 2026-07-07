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
use Storyblok\Bundle\Block\BlockRegistry;
use Storyblok\Bundle\Maker\MakeStoryblokBlock;
use Storyblok\Bundle\Maker\RemoteComponent;
use Storyblok\Bundle\Maker\SchemaMapper;
use Storyblok\Bundle\Maker\Storyblok\ComponentProviderInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Bundle\MakerBundle\Util\TemplateComponentGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use function Safe\file_get_contents;
use function Safe\fopen;
use function Safe\fwrite;
use function Safe\rewind;

/**
 * Drives the maker end to end with a real MakerBundle Generator writing to a temporary
 * directory (the "App\" namespace is mapped to it at runtime).
 */
final class MakeStoryblokBlockGenerationTest extends TestCase
{
    private string $tmpDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = \sprintf('%s/storyblok_maker_%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));
        $this->filesystem->mkdir($this->tmpDir);

        (new ComposerAutoloaderFinder('App'))->getClassLoader()->addPsr4('App\\', $this->tmpDir, true);

        BlockRegistry::$blocks = [];
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
        BlockRegistry::$blocks = [];
    }

    #[Test]
    public function generatesSelectedBlockWithChildrenEnumsAndTemplates(): void
    {
        $maker = new MakeStoryblokBlock(self::provider(), new SchemaMapper(), new BlockRegistry());

        $command = new Command('make:storyblok:block');
        $maker->configureCommand($command, new InputConfiguration());

        $input = new StringInput('');
        $input->bind($command->getDefinition());
        $input->setStream(self::inputStream("0\n"));

        $io = new ConsoleStyle($input, new BufferedOutput());

        $maker->interact($input, $io, $command);
        $maker->generate($input, $io, $this->generator());

        // Parent and whitelisted child are grouped in the CardRow directory.
        self::assertFileExists($this->tmpDir.'/Block/CardRow/CardRow.php');
        self::assertFileExists($this->tmpDir.'/Block/CardRow/CardRowItem.php');
        // The child's option field became an enum in the same directory's Enum namespace.
        self::assertFileExists($this->tmpDir.'/Block/CardRow/Enum/CardRowItemStyle.php');
        // Templates mirror the class directory.
        self::assertFileExists($this->tmpDir.'/templates/blocks/card_row/card_row.html.twig');
        self::assertFileExists($this->tmpDir.'/templates/blocks/card_row/card_row_item.html.twig');

        $parent = self::read($this->tmpDir.'/Block/CardRow/CardRow.php');
        self::assertStringContainsString('namespace App\Block\CardRow;', $parent);
        self::assertStringContainsString("#[AsBlock(name: 'card_row', template: 'blocks/card_row/card_row.html.twig')]", $parent);
        self::assertStringContainsString('/** @var list<CardRowItem> */', $parent);
        self::assertStringContainsString("self::list(\$values, 'items', CardRowItem::class, 1, 3)", $parent);

        $child = self::read($this->tmpDir.'/Block/CardRow/CardRowItem.php');
        self::assertStringContainsString('use App\Block\CardRow\Enum\CardRowItemStyle;', $child);
        self::assertStringContainsString("self::enum(\$values, 'style', CardRowItemStyle::class)", $child);

        $enum = self::read($this->tmpDir.'/Block/CardRow/Enum/CardRowItemStyle.php');
        self::assertStringContainsString('enum CardRowItemStyle: string', $enum);
        self::assertStringContainsString("case Default = 'default';", $enum);

        $template = self::read($this->tmpDir.'/templates/blocks/card_row/card_row.html.twig');
        self::assertStringContainsString('{# @var block \App\Block\CardRow\CardRow #}', $template);
    }

    #[Test]
    public function throwsWhenAllBlocksAlreadyExist(): void
    {
        // className just needs to be an existing class; the registry is keyed by block name.
        BlockRegistry::add(['className' => RemoteComponent::class, 'name' => 'card_row', 'template' => 'x']);
        BlockRegistry::add(['className' => SchemaMapper::class, 'name' => 'card_row_item', 'template' => 'x']);

        $maker = new MakeStoryblokBlock(self::provider(), new SchemaMapper(), new BlockRegistry());

        $command = new Command('make:storyblok:block');
        $maker->configureCommand($command, new InputConfiguration());

        $input = new StringInput('');
        $input->bind($command->getDefinition());
        $input->setStream(self::inputStream("0\n"));

        $io = new ConsoleStyle($input, new BufferedOutput());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already have a generated class');

        $maker->interact($input, $io, $command);
    }

    private static function provider(): ComponentProviderInterface
    {
        return new class() implements ComponentProviderInterface {
            public function components(): array
            {
                return [
                    new RemoteComponent('card_row', [
                        'title' => ['type' => 'text', 'required' => true, 'pos' => 0],
                        'items' => ['type' => 'bloks', 'required' => true, 'minimum' => 1, 'maximum' => 3, 'component_whitelist' => ['card_row_item'], 'pos' => 1],
                    ]),
                    new RemoteComponent('card_row_item', [
                        'label' => ['type' => 'text', 'required' => true, 'pos' => 0],
                        'style' => ['type' => 'option', 'required' => true, 'pos' => 1, 'options' => [
                            ['name' => 'Default', 'value' => 'default'],
                            ['name' => 'Highlight', 'value' => 'highlight'],
                        ]],
                    ]),
                ];
            }
        };
    }

    private function generator(): Generator
    {
        $fileManager = new FileManager(
            $this->filesystem,
            new AutoloaderUtil(new ComposerAutoloaderFinder('App')),
            new MakerFileLinkFormatter(null),
            $this->tmpDir,
            $this->tmpDir.'/templates',
        );

        return new Generator($fileManager, 'App', null, new TemplateComponentGenerator(true, false, 'App'));
    }

    private static function read(string $path): string
    {
        return file_get_contents($path);
    }

    /**
     * @return resource
     */
    private static function inputStream(string $content)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }
}
