<?php

namespace Hexafuchs\LaminasSecurity\Classes;

use Hexafuchs\LaminasSecurity\Enums\CheckState;
use Hexafuchs\LaminasSecurity\Services\ConsoleStyle;

class Audit
{
    /**
     * @var string $name
     */
    private string $name;

    /**
     * @var \Hexafuchs\LaminasSecurity\Classes\Report[] $reports
     */
    private array $reports;

    /**
     * @param string $name
     * @param \Hexafuchs\LaminasSecurity\Classes\Report[] $reports
     */
    public function __construct(string $name, array $reports)
    {
        $this->name    = ucfirst(strtolower($name));
        $this->reports = $reports;
    }

    /**
     * Runs all Reports that are part of the audit.
     *
     * @param \Hexafuchs\LaminasSecurity\Services\ConsoleStyle $io
     * @return void
     */
    public function run(ConsoleStyle $io): void
    {
        foreach ($this->reports as $report) {
            $io->section($report->getName());
            $report->run($io);
        }
    }

    /**
     * Returns the name of the audit.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns a summary of the report that can be printed as a table.
     *
     * @return array
     */
    public function getSummary(): array
    {
        $summary   = [];
        $failed    = 0;
        $warned    = 0;
        $succeeded = 0;
        $skipped   = 0;

        foreach ($this->reports as $report) {
            $result = [
                $report->getName(),
                $report->getFailedChecks(),
                $report->getWarnedChecks(),
                $report->getSucceededChecks(),
                $report->getSkippedChecks()
            ];

            $failed    += $result[1];
            $warned    += $result[2];
            $succeeded += $result[3];
            $skipped   += $result[4];

            $summary[] = $result;
        }

        $summary[] = [
            'Total',
            $failed,
            $warned,
            $succeeded,
            $skipped
        ];

        return $summary;
    }

    /**
     * Returns the worst state of the audit.
     *
     * @return \Hexafuchs\LaminasSecurity\Enums\CheckState
     */
    public function getState(): CheckState
    {
        $state = CheckState::SKIPPED;

        foreach ($this->reports as $report) {
            $reportState = $report->getState();

            if ($reportState === CheckState::FAILED) {
                return CheckState::FAILED;
            } else if ($reportState === CheckState::WARNED) {
                $state = CheckState::WARNED;
            } else if ($reportState === CheckState::SUCCEEDED && $state !== CheckState::WARNED) {
                $state = CheckState::SUCCEEDED;
            }
        }

        return $state;
    }
}