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
 * (skipping the ones that already have a generated class), reads its field schema and
 * generates the matching `#[AsBlock]` value object plus a template stub. When the selected
 * block restricts a "bloks" field to specific child components, those children are generated
 * too, grouped in a directory named after the parent block.
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
    public const string TEMPLATE_DIR = 'blocks';
    private ?RemoteComponent $component = null;

    /**
     * @var array<string, RemoteComponent>
     */
    private array $components = [];

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

    /**
     * Returns the names of the child components a block (and its descendants) restricts its
     * "bloks" fields to, deduplicated and limited to components that actually exist.
     *
     * @param array<string, RemoteComponent> $components
     *
     * @return list<string>
     */
    public static function childComponentNames(string $rootName, array $components): array
    {
        $found = [];
        $visited = [$rootName => true];
        $queue = [$rootName];

        while ([] !== $queue) {
            $current = \array_shift($queue);

            foreach ($components[$current]->schema as $field) {
                if ('bloks' !== ($field['type'] ?? null)) {
                    continue;
                }

                $whitelist = \is_array($field['component_whitelist'] ?? null) ? $field['component_whitelist'] : [];

                foreach ($whitelist as $childName) {
                    if (!\is_string($childName) || isset($visited[$childName]) || !isset($components[$childName])) {
                        continue;
                    }

                    $visited[$childName] = true;
                    $found[] = $childName;
                    $queue[] = $childName;
                }
            }
        }

        return $found;
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Namespace for the generated block classes', self::NAMESPACE_PREFIX)
            ->setHelp(<<<'TXT'
                The <info>%command.name%</info> command connects to your Storyblok space, lists the
                available components (blocks) and generates a block class and Twig template for the
                one you select.

                Blocks that already have a generated class are listed and cannot be selected.

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
        $existing = [];

        foreach ($components as $component) {
            $this->components[$component->name] = $component;

            if ($this->blockRegistry->has($component->name)) {
                $existing[] = $component->name;

                continue;
            }

            $choices[$component->name] = $component;
        }

        if ([] !== $existing) {
            \sort($existing);
            $io->text('The following blocks already have a generated class and are skipped:');
            $io->listing($existing);
        }

        if ([] === $choices) {
            throw new \RuntimeException('All Storyblok blocks already have a generated class.');
        }

        $selected = $io->choice('Select the Storyblok block you want to generate', array_keys($choices));

        $this->component = $choices[$selected];
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        if (!$this->component instanceof RemoteComponent) {
            throw new \LogicException('No component was selected.');
        }

        $base = self::namespaceFrom($input);
        $childNames = self::childComponentNames($this->component->name, $this->components);

        // When a block restricts a "bloks" field to specific children, group the parent and
        // its children in a directory (namespace) named after the parent block.
        $namespace = [] !== $childNames
            ? $base.Str::asClassName($this->component->name).'\\'
            : $base;

        $unmapped = $this->generateBlock($generator, $this->component, $namespace);

        foreach ($childNames as $childName) {
            // Do not overwrite children that are already generated.
            if ($this->blockRegistry->has($childName)) {
                continue;
            }

            $unmapped = [...$unmapped, ...$this->generateBlock($generator, $this->components[$childName], $namespace)];
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        if ([] !== $unmapped) {
            $io->note(\sprintf(
                'The following field(s) could not be mapped and were left as "// @TODO": %s.',
                \implode(', ', $unmapped),
            ));
        }
    }

    /**
     * Generates the block class, its Twig template and any generated enums for a single
     * component and returns the list of field keys that could not be mapped.
     *
     * @return list<string>
     */
    private function generateBlock(Generator $generator, RemoteComponent $component, string $namespace): array
    {
        $className = $namespace.Str::asClassName($component->name);
        $templatePath = \sprintf('%s/%s.html.twig', self::TEMPLATE_DIR, Str::asSnakeCase($component->name));
        $properties = $this->schemaMapper->map($component->schema, $component->name);

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
            'block_name' => $component->name,
            'block_template' => $templatePath,
        ]);

        $generator->generateTemplate($templatePath, __DIR__.'/templates/block_template.tpl.php', [
            'block_fqcn' => $className,
        ]);

        return \array_values(\array_map(
            static fn (Property $property): string => $property->key,
            \array_filter($properties, static fn (Property $property): bool => $property->isUnmapped()),
        ));
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
