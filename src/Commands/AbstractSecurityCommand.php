<?php

namespace Hexafuchs\LaminasSecurity\Commands;

use Hexafuchs\LaminasSecurity\Enums\CheckState;
use Hexafuchs\LaminasSecurity\Services\CheckLoader;
use Symfony\Component\Console\Command\Command;

abstract class AbstractSecurityCommand extends Command
{
    /**
     * @var \Hexafuchs\LaminasSecurity\Services\CheckLoader
     */
    protected CheckLoader $checkLoader;

    public function __construct(CheckLoader $checkLoader, string $name = null)
    {
        $this->checkLoader = $checkLoader;
        parent::__construct($name);
    }

    /**
     * Returns the appropriate exit-code based on the given CheckState.
     *
     * @param \Hexafuchs\LaminasSecurity\Enums\CheckState $state
     * @return int
     */
    protected function exitCode(CheckState $state): int
    {
        return match ($state) {
            CheckState::WARNED                         => 2,
            CheckState::SUCCEEDED, CheckState::SKIPPED => 0,
            default                                    => 1
        };
    }
}