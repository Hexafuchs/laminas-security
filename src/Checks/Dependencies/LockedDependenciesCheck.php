<?php

namespace Hexafuchs\LaminasSecurity\Checks\Dependencies;

use Hexafuchs\LaminasSecurity\Services\ShellExecutor;

class LockedDependenciesCheck extends AbstractDependenciesCheck
{
    /**
     * @param \Hexafuchs\LaminasSecurity\Services\ShellExecutor $shell
     */
    public function __construct(
        protected readonly ShellExecutor $shell
    ) {}

    /**
     * @inheritDoc
     */
    public function getCheckName(): string
    {
        return 'Backend-Dependencies are in sync with composer.lock';
    }

    /**
     * @inheritDoc
     */
    protected function run(): void
    {
        if (!$this->shell->commandExists('composer')) {
            $this->appendDetails([
                'Cannot find <skip>composer</skip> executable.'
            ]);

            return;
        }

        if (!(
            str_contains(
                $this->shell->runCommand('composer', 'install', '--dry-run', '--no-dev')['stderr'],
                'Nothing to install, update or remove'
            ) || str_contains(
                $this->shell->runCommand('composer', 'install', '--dry-run')['stderr'],
                'Nothing to install, update or remove'
            )
        )) {
            $this->fail([
                'Some dependencies are <warn>not</warn> in sync with your composer.lock',
                'You can use <warn>composer</warn> to check for updates using the following commands:',
                '-    <mark>List</mark> changes, <skip>excluding Dev-Dependencies</skip>: ' .
                '<cmd-warn> composer <cmd-success>install</cmd-success> <cmd-mark>--dry-run</cmd-mark> <cmd-skip>--no-dev</cmd-skip> </cmd-warn>',
                '-    <mark>List</mark> changes, including Dev-Dependencies: ' .
                '<cmd-warn> composer <cmd-success>install</cmd-success> <cmd-mark>--dry-run</cmd-mark>          </cmd-warn>',
                '- Execute changes, <skip>excluding Dev-Dependencies</skip>: ' .
                '<cmd-warn> composer <cmd-success>install</cmd-success> <cmd-skip>--no-dev</cmd-skip>           </cmd-warn>',
                '- Execute changes, including Dev-Dependencies: ' .
                '<cmd-warn> composer <cmd-success>install</cmd-success>                    </cmd-warn>',
            ]);
        }

        $this->finish();
    }
}