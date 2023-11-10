<?php

namespace Hexafuchs\LaminasSecurity\Commands;

use Hexafuchs\LaminasSecurity\Enums\CheckState;
use Hexafuchs\LaminasSecurity\Services\ConsoleStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SecurityReportCommand extends AbstractSecurityCommand
{
    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Runs the specified security report and prints the summary')
            ->addArgument('report', InputArgument::OPTIONAL, 'The name of the report that should be executed');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     * @throws \Hexafuchs\LaminasSecurity\Exceptions\UnknownReportException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ConsoleStyle($output);

        if ($input->getArgument('report') === null) {
            return $this->printReportNames($io);
        }

        $report = $this->checkLoader->createReport($input->getArgument('report'));

        $io->title($report->getName());
        $report->run($io);
        $io->horizontalTable(CheckState::formats(), [[
            $report->getFailedChecks(),
            $report->getWarnedChecks(),
            $report->getSucceededChecks(),
            $report->getSkippedChecks()
        ]]);

        return $this->exitCode($report->getState());
    }

    /**
     * Prints all available report names
     *
     * @param \Hexafuchs\LaminasSecurity\Services\ConsoleStyle $io
     * @return int
     */
    private function printReportNames(ConsoleStyle $io): int
    {
        $io->error([
            '',
            'No report given, you can choose between the following reports:',
            ...array_map(
                fn(string $report) => sprintf('  - <fg=default>%s</>', $report),
                $this->checkLoader->getReportNames()
            ),
            '',
            sprintf("<info>%s \<report\></info>", $this->getName()),
            ''
        ]);

        return Command::FAILURE;
    }
}