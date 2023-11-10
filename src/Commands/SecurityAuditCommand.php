<?php

namespace Hexafuchs\LaminasSecurity\Commands;

use Hexafuchs\LaminasSecurity\Enums\CheckState;
use Hexafuchs\LaminasSecurity\Services\ConsoleStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SecurityAuditCommand extends AbstractSecurityCommand
{

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Runs the specified security audit and prints the summary')
            ->addArgument('audit', InputArgument::OPTIONAL, 'The audit to run (runs all reports if not specified)');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     * @throws \Hexafuchs\LaminasSecurity\Exceptions\UnknownAuditException
     * @throws \Hexafuchs\LaminasSecurity\Exceptions\UnknownReportException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ConsoleStyle($output);
        $audit = $this->checkLoader->createAudit($input->getArgument('audit'));

        $io->title($audit->getName() . ' Scan');
        $audit->run($io);

        $io->section('Summary');
        $io->table(['<bold-mark>Report</>', ...CheckState::formats()], $audit->getSummary());

        return $this->exitCode($audit->getState());
    }
}