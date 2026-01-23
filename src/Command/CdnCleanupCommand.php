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

namespace Storyblok\Bundle\Command;

use Safe\DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use function Safe\json_decode;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
#[AsCommand(
    name: 'storyblok:cdn:cleanup',
    description: 'Cleans up CDN cached files',
)]
final class CdnCleanupCommand extends Command
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $storagePath,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only show what would be deleted without actually deleting')
            ->addOption('expired', null, InputOption::VALUE_NONE, 'Only delete expired files based on metadata')
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command cleans up CDN cached files.

To delete all cached files:

    <info>php %command.full_name%</info>

To only show what would be deleted (dry-run):

    <info>php %command.full_name% --dry-run</info>

To only delete expired files:

    <info>php %command.full_name% --expired</info>

HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $expiredOnly = $input->getOption('expired');

        if (!$this->filesystem->exists($this->storagePath)) {
            $io->success('CDN storage directory does not exist. Nothing to clean up.');

            return Command::SUCCESS;
        }

        $finder = new Finder();
        $finder->directories()->in($this->storagePath)->depth(0);

        if (!$finder->hasResults()) {
            $io->success('No cached files found.');

            return Command::SUCCESS;
        }

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($finder as $directory) {
            if ($expiredOnly && !self::isExpired($directory->getRealPath())) {
                ++$skippedCount;

                continue;
            }

            if ($dryRun) {
                $io->writeln(\sprintf('Would delete: <comment>%s</comment>', $directory->getFilename()));
            } else {
                $this->filesystem->remove($directory->getRealPath());
                $io->writeln(\sprintf('Deleted: <info>%s</info>', $directory->getFilename()), OutputInterface::VERBOSITY_VERBOSE);
            }

            ++$deletedCount;
        }

        if ($dryRun) {
            $io->note(\sprintf('Dry-run: %d directories would be deleted, %d skipped.', $deletedCount, $skippedCount));
        } else {
            $io->success(\sprintf('Cleanup complete: %d directories deleted, %d skipped.', $deletedCount, $skippedCount));
        }

        return Command::SUCCESS;
    }

    private static function isExpired(string $directoryPath): bool
    {
        $metadataFinder = new Finder();
        $metadataFinder->files()->in($directoryPath)->name('*.json');

        foreach ($metadataFinder as $metadataFile) {
            $content = $metadataFile->getContents();

            try {
                $metadata = json_decode($content, true);
            } catch (\JsonException) {
                continue;
            }

            if (!\is_array($metadata)) {
                continue;
            }

            if (!isset($metadata['expiresAt']) || !\is_string($metadata['expiresAt'])) {
                continue;
            }

            try {
                $expiresAt = new DateTimeImmutable($metadata['expiresAt']);
            } catch (\Exception) {
                continue;
            }

            if ($expiresAt < new DateTimeImmutable()) {
                return true;
            }
        }

        return false;
    }
}
