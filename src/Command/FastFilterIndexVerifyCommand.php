<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Command;

use LeadingSystems\MerconisBundle\FastFilter\FastFilterIndexVerifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FastFilterIndexVerifyCommand extends Command
{
    protected static $defaultName = 'merconis:fast-filter:verify-index';

    public function __construct(
        private readonly FastFilterIndexVerifier $indexVerifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Verifies the Merconis fast filter read model.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $verificationErrorsByCheck = $this->indexVerifier->verify();
        $hasErrors = false;

        foreach ($verificationErrorsByCheck as $checkName => $verificationErrors) {
            if ($verificationErrors === []) {
                $output->writeln(sprintf('%s: OK', $checkName));
                continue;
            }

            $hasErrors = true;
            $output->writeln(sprintf('%s: FAILED', $checkName));

            foreach ($verificationErrors as $verificationError) {
                $output->writeln('  - ' . $verificationError);
            }
        }

        if ($hasErrors) {
            return Command::FAILURE;
        }

        $output->writeln('Fast filter index verification completed successfully.');

        return Command::SUCCESS;
    }
}
