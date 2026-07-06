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

use Storyblok\Bundle\Block\Attribute\AsBlock;
use Storyblok\Bundle\Block\BlockRegistry;
use Storyblok\Bundle\Maker\Storyblok\ComponentProviderInterface;
use Storyblok\Bundle\Util\ValueObjectTrait;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Generates a block class (and its Twig template) from a Storyblok component schema.
 *
 * Lists the components of the configured Storyblok space, lets the developer pick one
 * (flagging the ones that already have a generated class), reads its field schema and
 * generates the matching `#[AsBlock]` value object plus a template stub.
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class MakeStoryblokBlock extends AbstractMaker
{
    /**
     * The namespace the generated block classes are placed in (consumer application).
     */
    public const string NAMESPACE_PREFIX = 'App\\Block\\';

    /**
     * The directory (relative to the application "templates/" directory) the generated
     * Twig templates are placed in.
     */
    public const string TEMPLATE_DIR = 'block';
    private ?RemoteComponent $component = null;

    public function __construct(
        private readonly ComponentProviderInterface $componentProvider,
        private readonly SchemaMapper $schemaMapper,
        private readonly BlockRegistry $blockRegistry,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:storyblok:block';
    }

    public static function getCommandDescription(): string
    {
        return 'Generate a block class from a Storyblok component';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Namespace for the generated block classes', self::NAMESPACE_PREFIX)
            ->setHelp(<<<'TXT'
                The <info>%command.name%</info> command connects to your Storyblok space, lists the
                available components (blocks) and generates a block class and Twig template for the
                one you select.

                Components that already have a generated class are flagged with <comment>[already exists]</comment>.

                    <info>php %command.full_name%</info>

                By default the classes are generated in the <comment>App\Block</comment> namespace. Pass
                <info>--namespace</info> to change it:

                    <info>php %command.full_name% --namespace="App\Storyblok\Block"</info>

                TXT);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $components = $this->componentProvider->components();

        if ([] === $components) {
            throw new \RuntimeException('No Storyblok components were found in the configured space.');
        }

        $choices = [];

        foreach ($components as $component) {
            $label = $component->name.($this->blockRegistry->has($component->name) ? ' [already exists]' : '');
            $choices[$label] = $component;
        }

        $selected = $io->choice('Select the Storyblok block you want to generate', array_keys($choices));

        $this->component = $choices[$selected];
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        if (!$this->component instanceof RemoteComponent) {
            throw new \LogicException('No component was selected.');
        }

        $namespace = self::namespaceFrom($input);
        $className = $namespace.Str::asClassName($this->component->name);
        $templatePath = \sprintf('%s/%s.html.twig', self::TEMPLATE_DIR, Str::asSnakeCase($this->component->name));
        $properties = $this->schemaMapper->map($this->component->schema, $this->component->name);

        $classData = ClassData::create(
            class: $className,
            suffix: '',
            useStatements: [
                AsBlock::class,
                ValueObjectTrait::class,
            ],
        );
        $classData->setIsFinal(true);

        foreach ($properties as $property) {
            if (null !== $property->enum) {
                $enumFqcn = $namespace.'Enum\\'.$property->enum->shortName;
                $classData->addUseStatement($enumFqcn);

                $generator->generateClass($enumFqcn, __DIR__.'/templates/Enum.tpl.php', [
                    'cases' => $property->enum->cases,
                ]);

                continue;
            }

            if (null !== $property->type && null !== $useStatement = $property->type->useStatement()) {
                $classData->addUseStatement($useStatement);
            }
        }

        $generator->generateClassFromClassData($classData, __DIR__.'/templates/Block.tpl.php', [
            'properties' => $properties,
            'block_name' => $this->component->name,
            'block_template' => $templatePath,
        ]);

        $generator->generateTemplate($templatePath, __DIR__.'/templates/block_template.tpl.php', [
            'block_fqcn' => $className,
        ]);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $unmapped = \array_values(\array_filter($properties, static fn (Property $property): bool => $property->isUnmapped()));

        if ([] !== $unmapped) {
            $io->note(\sprintf(
                'The following field(s) could not be mapped and were left as "// @TODO": %s.',
                \implode(', ', \array_map(static fn (Property $property): string => $property->key, $unmapped)),
            ));
        }
    }

    private static function namespaceFrom(InputInterface $input): string
    {
        $namespace = $input->getOption('namespace');

        if (!\is_string($namespace) || '' === \trim($namespace, '\\')) {
            return self::NAMESPACE_PREFIX;
        }

        return \trim($namespace, '\\').'\\';
    }
}
