<?php

namespace Hexafuchs\LaminasSecurity\Checks\Dependencies;

use Hexafuchs\LaminasSecurity\Services\ShellExecutor;

class VulnerableBackendDependenciesCheck extends AbstractDependenciesCheck
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
        return 'No known vulnerable dependencies installed in backend';
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

        $response = json_decode(
            $this->shell->runCommand('composer', 'audit', '--no-scripts', '--format=json')['stdout'],
            true
        );

        if (array_key_exists('advisories', $response)) {
            foreach ($response['advisories'] as $package => $advisories) {
                $messages = [sprintf('- Vulnerable package <fail>%s</fail> found', $package)];

                foreach ($advisories as $advisory) {
                    $messages[] = sprintf('  <fail>%s</fail>: %s', $advisory['cve'], $advisory['link']);
                }

                $this->fail($messages);
            }
        }

        if (array_key_exists('abandoned', $response)) {
            foreach ($response['abandoned'] as $package => $alternative) {
                $this->warn([
                    sprintf('- Abandoned package <warn>%s</warn> found', $package),
                    sprintf('  possible replacement: <mark>%s</mark>', $alternative)
                ]);
            }
        }

        if (!empty($response['advisories']) || !empty($response['abandoned'])) {
            $this->appendDetails([
                '',
                'Execute <cmd-warn> composer <cmd-success>audit</cmd-success> </cmd-warn> for further details.'
            ]);
        }

        $this->finish();
    }
}