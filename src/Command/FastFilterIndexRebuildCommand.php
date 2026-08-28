<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Command;

use LeadingSystems\MerconisBundle\FastFilter\FastFilterIndexBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FastFilterIndexRebuildCommand extends Command
{
    protected static $defaultName = 'merconis:fast-filter:rebuild-index';

    public function __construct(
        private readonly FastFilterIndexBuilder $indexBuilder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Rebuilds the Merconis fast filter read model.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $counts = $this->indexBuilder->rebuild();

        foreach ($counts as $label => $count) {
            $output->writeln($label . ': ' . $count);
        }

        return Command::SUCCESS;
    }
}
