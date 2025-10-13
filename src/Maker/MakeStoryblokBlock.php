<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Maker;

use Storyblok\Bundle\Block\Attribute\AsBlock;
use Storyblok\Bundle\Util\ValueObjectTrait;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use function PHPUnit\Framework\matches;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class MakeStoryblokBlock extends AbstractMaker
{
    private ClassData $classData;

    public static function getCommandName(): string
    {
        return 'make:storyblok:block';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new block class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, \sprintf('Use the technical name you have configured your block in Storyblok with (e.g. <fg=yellow>%s</>)', Str::asSnakeCase(Str::getRandomTerm())))
            ->setHelp(<<<'TXT'
The <info>%command.name%</info> command generates a new block class.

<info>php %command.full_name% hero_section</info>

If the argument is missing, the command will ask for the technical name interactively.
TXT
            );
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $this->classData = ClassData::create(
            class: \sprintf('App\\Blocks\\%s', Str::asClassName($input->getArgument('name'))),
            suffix: '',
            useStatements: [
                AsBlock::class,
                ValueObjectTrait::class,
            ],
        );

        $this->classData->setIsFinal(true);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $fields = $this->getPropertyNames($this->classData->getFullClassName());

        $isFirstField = true;
        $newFields = [];
        while (true) {
            $newField = $this->askForNextField($io, $fields, $isFirstField);
            $isFirstField = false;

            if (null === $newField) {
                break;
            }

            if (null !== $useStatement = $newField->type->useStatement()) {
                $this->classData->addUseStatement($useStatement);
            }

            $newFields[] = $newField;
        }

        $generator->generateClassFromClassData($this->classData, __DIR__.'/templates/Block.tpl.php', [
            'properties' => $newFields,
        ]);
        $generator->writeChanges();
    }

    /**
     * @param string[] $fields
     */
    private function askForNextField(ConsoleStyle $io, array $fields, bool $isFirstField): ?Property
    {
        $io->writeln('');

        if ($isFirstField) {
            $questionText = 'New property name (press <return> to stop adding fields)';
        } else {
            $questionText = 'Add another property? Enter the property name (or press <return> to stop adding fields)';
        }

        $fieldName = $io->ask($questionText, null, function ($name) use ($fields) {
            // allow it to be empty
            if (!$name) {
                return $name;
            }

            if (\in_array($name, $fields)) {
                throw new \InvalidArgumentException(\sprintf('The "%s" property already exists.', $name));
            }

            return Str::asLowerCamelCase($name);
        });

        if (null === $fieldName) {
            return null;
        }

        $type = null;
        $defaultType = TypeGuesser::guessType(Str::asSnakeCase($fieldName));
        $availableTypes = \array_map(static fn(Type $type) => $type->value, Type::cases());

        while (null === $type) {
            $question = new Question('Field type (enter <comment>?</comment> to see all types)', $defaultType->value);
            $question->setAutocompleterValues($availableTypes);
            $type = $io->askQuestion($question);

            if ('?' === $type) {
                $io->listing($availableTypes);
                $io->writeln('');
                $type = null;
            } elseif (!\in_array($type, $availableTypes)) {
                $io->listing($availableTypes);
                $io->error(\sprintf('Invalid type "%s".', $type));
                $io->writeln('');

                $type = null;
            }
        }

        $classProperty = new Property(name: $fieldName, type: Type::from($type));

        if ('string' === $type) {
            $classProperty->maxLength = $io->ask('Maximum field length');
//        } elseif ('decimal' === $type) {
//            // 10 is the default value given in \Doctrine\DBAL\Schema\Column::$_precision
//            $classProperty->precision = $io->ask('Precision (total number of digits stored: 100.00 would be 5)', '10', Validator::validatePrecision(...));
//
//            // 0 is the default value given in \Doctrine\DBAL\Schema\Column::$_scale
//            $classProperty->scale = $io->ask('Scale (number of decimals to store: 100.00 would be 2)', '0', Validator::validateScale(...));
        } elseif ('enum' === $type) {
            // ask for valid backed enum class
            $fqcn = $io->ask('Enum class');
            $classProperty->class = Str::getShortClassName($fqcn);
            $this->classData->addUseStatement($fqcn);
        } elseif ('list' === $type) {
            $fqcn = $io->ask('Object class');
            $classProperty->class = Str::getShortClassName($fqcn);
            $this->classData->addUseStatement($fqcn);
        } elseif ('blocks' === $type) {
            $classProperty->min = (int) $io->ask('Min items count');
            $classProperty->max = (int) $io->ask('Max items count');
        }

        if ($io->confirm('Is this property optional', false)) {
            $classProperty->nullable = true;
        }

        return $classProperty;
    }

    private function getPropertyNames(string $class): array
    {
        if (!class_exists($class)) {
            return [];
        }

        $reflectionClass = new \ReflectionClass($class);

        return \array_map(
            static fn (\ReflectionProperty $property) => $property->getName(),
            $reflectionClass->getProperties(),
        );
    }
}
