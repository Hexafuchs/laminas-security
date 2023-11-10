<?php

namespace Hexafuchs\LaminasSecurity\Checks;

use Hexafuchs\LaminasSecurity\Enums\CheckState;
use Throwable;

abstract class AbstractCheck
{
    /**
     * Key to access the laminas-security configuration if the check has access to the application-config
     */
    protected const LAMINAS_SECURITY_CONFIGURATION_KEY = 'laminas-security';

    /**
     * @var \Hexafuchs\LaminasSecurity\Enums\CheckState $state
     */
    private CheckState $state = CheckState::SKIPPED;

    /**
     * @var string[] $details
     */
    private array $details = [];

    /**
     * Returns the name of the check that is displayed in the output.
     *
     * @return string
     */
    public abstract function getCheckName(): string;

    /**
     * Returns the name of the report that this check is assigned to.
     *
     * @return string
     */
    public abstract function getReportName(): string;

    /**
     * Runs the check to define the result.
     *
     * @return void
     */
    protected abstract function run(): void;

    /**
     * Appends the given string to the Check-Details which are displayed in the output after the check is finished.
     *
     * @param string|array $details
     * @return void
     */
    protected function appendDetails(string|array $details = []): void
    {
        if (empty($details)) {
            return;
        }

        $details       = is_array($details) ? array_values($details) : [$details];
        $this->details = array_merge($this->details, $details);
    }

    /**
     * Sets the state to FAILED and appends the given details to the output.
     * Every other state will be overwritten.
     *
     * @param string|array $details
     * @return void
     */
    protected function fail(string|array $details = []): void
    {
        $this->appendDetails($details);
        $this->state = CheckState::FAILED;
    }

    /**
     * Sets the state to WARNED and appends the given details to the output.
     * The FAILED state won't be overwritten by this.
     *
     * @param string|array $details
     * @return void
     */
    protected function warn(string|array $details = []): void
    {
        $this->appendDetails($details);

        if ($this->state !== CheckState::FAILED) {
            $this->state = CheckState::WARNED;
        }
    }

    /**
     * Sets the state to SUCCEEDED and appends the given details to the output.
     * The states WARNED and FAILED won't be overwritten by this.
     *
     * @param string|array $details
     *
     * @return void
     */
    protected function success(string|array $details = []): void
    {
        $this->appendDetails($details);

        if ($this->state === CheckState::SKIPPED) {
            $this->state = CheckState::SUCCEEDED;
        }
    }

    /**
     * @see \Hexafuchs\LaminasSecurity\Checks\AbstractCheck::success()
     */
    protected function finish(string|array $details = []): void
    {
        $this->success($details);
    }

    /**
     * Returns the current CheckState.
     *
     * @return \Hexafuchs\LaminasSecurity\Enums\CheckState
     */
    public function getState(): CheckState
    {
        return $this->state;
    }

    /**
     * Returns all details.
     *
     * @return string[]
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Runs the check safely.
     * If the run throws an exception this will set the state to FAILED and append the Exception to the details.
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $this->run();
        } catch (Throwable $throwable) {
            $this->fail([
                $throwable::class . ': ' . $throwable->getMessage(),
                $throwable->getTraceAsString()
            ]);
        }
    }
}