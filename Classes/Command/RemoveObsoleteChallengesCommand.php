<?php
declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\Command;

use BBysaeth\Typo3Altcha\Services\AltchaService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveObsoleteChallengesCommand extends Command {

    public function __construct(protected AltchaService $altchaService)
    {
        parent::__construct();
    }
    protected function configure()
    {
        $this->setDescription('Remove obsolete challenges');
        $this->addOption('dry-run', null, null, 'Do not remove challenges, just show what would be removed');
        $this->addOption('include-solved-challenges', 'a', null, 'Include solved challenges');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = $input->getOption('dry-run');
        $includeSolvedChallenges = $input->getOption('include-solved-challenges');

        $output->writeln('Removing obsolete challenges');
        $count = $this->altchaService->removeObsoleteChallenges($dryRun, $includeSolvedChallenges);
        if($dryRun) {
            $output->writeln(sprintf('%d would have been removed. Dry run, no challenges were removed. ', $count));
            return Command::SUCCESS;
        }
        $output->writeln(sprintf('Removed %d challenges', $count));
        return Command::SUCCESS;
    }
}