<?php

namespace Hexafuchs\LaminasSecurity\Checks\Dependencies;

use Hexafuchs\LaminasSecurity\Services\ShellExecutor;

class StableDependenciesCheck extends AbstractDependenciesCheck
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
        return 'Stable versions of Backend-Dependencies are preferred';
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

        $result = $this->shell->runCommand(
            'composer', 'update', '--prefer-stable', '--dry-run', '--no-install'
        );

        $count = preg_match_all('/- (Upgrading|Downgrading) (.*?\/.*?) \((.*?) => (.*?)\)/', $result['stderr'], $matches, PREG_SET_ORDER);

        if ($count > 0) {
            $this->fail('The following dependencies are <fail>not on stable versions</fail> and should be up-/downgraded:');

            foreach ($matches as $match) {
                if ($match[1] === 'Upgrading') {
                    $this->fail(sprintf(
                        '- <mark>%s</mark> should be <warn>upgraded</warn> (<fail>%s</fail> => <success>%s</success>)',
                        $match[2], $match[3], $match[4]
                    ));
                } else {
                    $this->fail(sprintf(
                        '- <mark>%s</mark> should be <warn>downgraded</warn> (<fail>%s</fail> => <success>%s</success>)',
                        $match[2], $match[3], $match[4]
                    ));
                }
            }

            $this->appendDetails([
                '',
                'You can use <warn>composer</warn> for further details using the following commands:',
                '-    <mark>List</mark> recommended changes: ' .
                '<cmd-warn> composer <cmd-success>update</cmd-success> <cmd-skip>--prefer-stable</cmd-skip> <cmd-mark>--dry-run</cmd-mark> </cmd-warn>',
                '- <mark>Execute</mark> recommended changes: ' .
                '<cmd-warn> composer <cmd-success>update</cmd-success> <cmd-skip>--prefer-stable</cmd-skip>           </cmd-warn>',
            ]);
        }

        $this->finish();
    }
}