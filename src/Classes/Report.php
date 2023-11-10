<?php

namespace Hexafuchs\LaminasSecurity\Classes;

use Hexafuchs\LaminasSecurity\Checks\AbstractCheck;
use Hexafuchs\LaminasSecurity\Enums\CheckState;
use Hexafuchs\LaminasSecurity\Services\ConsoleStyle;

class Report
{
    /**
     * @var string $name
     */
    private string $name;

    /**
     * @var \Hexafuchs\LaminasSecurity\Checks\AbstractCheck[] $checks
     */
    private array $checks;

    /**
     * @var int $failedChecks
     */
    private int $failedChecks = 0;

    /**
     * @var int $warnedChecks
     */
    private int $warnedChecks = 0;

    /**
     * @var int $succeededChecks
     */
    private int $succeededChecks = 0;

    /**
     * @var int $skippedChecks
     */
    private int $skippedChecks = 0;

    /**
     * @param string $name
     * @param array $checks
     */
    public function __construct(string $name, array $checks)
    {
        $this->name   = ucwords(strtolower($name));
        $this->checks = $checks;
    }

    /**
     * Runs all checks within the report
     *
     * @param \Hexafuchs\LaminasSecurity\Services\ConsoleStyle $io
     * @return void
     */
    public function run(ConsoleStyle $io): void
    {
        $lastIdx = array_key_last($this->checks);

        foreach ($this->checks as $idx => $check) {
            $io->indicator(
                '<mark>⧗ </>' . $check->getCheckName() . ': <mark>Running</>',
                fn() => $this->resultGeneration($check)
            );

            $details = $check->getDetails();

            if (!empty($details)) {
                if ($details[array_key_last($details)] !== '' && $idx !== $lastIdx) {
                    $details[] = '';
                }

                $io->indent($details);
            }

            switch ($check->getState()) {
                case CheckState::SKIPPED:
                    $this->skippedChecks++;
                    break;
                case CheckState::SUCCEEDED:
                    $this->succeededChecks++;
                    break;
                case CheckState::WARNED:
                    $this->warnedChecks++;
                    break;
                case CheckState::FAILED:
                    $this->failedChecks++;
                    break;
            }
        }
    }

    /**
     * Executes the check and returns the matching header for the result
     *
     * @param \Hexafuchs\LaminasSecurity\Checks\AbstractCheck $check
     * @return string
     */
    private function resultGeneration(AbstractCheck $check): string
    {
        $check->execute();
        return $check->getState()->symbol() . $check->getCheckName() . ': ' . $check->getState()->format();
    }

    /**
     * Returns the name of the report.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the number of failed checks within the report.
     *
     * @return int
     */
    public function getFailedChecks(): int
    {
        return $this->failedChecks;
    }

    /**
     * Returns the number of warned checks within the report.
     *
     * @return int
     */
    public function getWarnedChecks(): int
    {
        return $this->warnedChecks;
    }

    /**
     * Returns the number of succeeded checks within the report.
     *
     * @return int
     */
    public function getSucceededChecks(): int
    {
        return $this->succeededChecks;
    }

    /**
     * Returns the number of skipped checks within the report.
     *
     * @return int
     */
    public function getSkippedChecks(): int
    {
        return $this->skippedChecks;
    }

    /**
     * Returns the worst CheckState within the report.
     *
     * @return \Hexafuchs\LaminasSecurity\Enums\CheckState
     */
    public function getState(): CheckState
    {
        if ($this->failedChecks > 0) {
            return CheckState::FAILED;
        }

        if ($this->warnedChecks > 0) {
            return CheckState::WARNED;
        }

        if ($this->succeededChecks > 0) {
            return CheckState::SUCCEEDED;
        }

        return CheckState::SKIPPED;
    }
}