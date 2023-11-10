<?php

namespace Hexafuchs\LaminasSecurity\Checks\Dependencies;

use Hexafuchs\LaminasSecurity\Enums\CheckState;
use Hexafuchs\LaminasSecurity\Services\ShellExecutor;

class VulnerableFrontendDependenciesCheck extends AbstractDependenciesCheck
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
        return 'No known vulnerable dependencies installed in frontend';
    }

    /**
     * @inheritDoc
     */
    protected function run(): void
    {
        if (!file_exists('package.json')) {
            $this->appendDetails('No <skip>package.json</skip> found.');
            return;
        }

        if (!$this->checkWithYarn()) {
            if (!$this->checkWithNPM()) {
                $this->appendDetails('Cannot find any known <skip>NPM</skip>-Package-Manager.');
                return;
            }
        }

        $this->finish();
    }

    protected function appendSummary(string $cmd): void
    {
        if (in_array($this->getState(), [CheckState::WARNED, CheckState::FAILED])) {
            $this->appendDetails([
                '',
                sprintf('Execute <cmd-warn> %s <cmd-success>audit</cmd-success> </cmd-warn> for further details.', $cmd)
            ]);
        }
    }

    protected function checkWithYarn(): bool
    {
        if (!file_exists('yarn.lock') || !$this->shell->commandExists('yarn')) {
            return false;
        }

        $version = $this->shell->runCommand('yarn', '--version')['stdout'];

        if (str_starts_with($version, '1.')) {
            $responses = explode(
                "\n",
                $this->shell->runCommand('yarn', 'audit', '--json')['stdout']
            );

            foreach ($responses as $response) {
                if (empty($response)) {
                    continue;
                }

                $data = json_decode($response, true);

                if ($data['type'] === 'auditAdvisory') {
                    $this->reportVulnerability(
                        $data['data']['advisory']['module_name'],
                        $data['data']['advisory']['title'],
                        $data['data']['advisory']['url'],
                        $data['data']['advisory']['severity']
                    );
                }
            }

            $this->appendSummary('yarn');
        } else {
            $responses = explode(
                "\n",
                $this->shell->runCommand('yarn', 'npm', 'audit', '--json')['stdout']
            );

            foreach ($responses as $response) {
                if (empty($response)) {
                    continue;
                }

                $data = json_decode($response, true);
                $this->reportVulnerability(
                    $data['value'],
                    $data['children']['Issue'],
                    $data['children']['URL'],
                    $data['children']['Severity']
                );
            }

            $this->appendSummary('yarn npm');
        }

        return true;
    }

    protected function reportVulnerability(string $packageName, string $issue, string $url, string $severity): void
    {
        $prettySeverity = match (strtolower($severity)) {
            ''         => 'A',
            'low'      => '<warn>Low</warn>',
            'moderate' => '<warn>Moderate</warn>',
            'high'     => '<fail>High</fail>',
            'critical' => '<fail>Critical</fail>',
            default    => ucfirst(strtolower($severity))
        };

        $this->fail([
            sprintf('- %s Vulnerability in package <fail>%s</fail> found', $prettySeverity, $packageName),
            '      <mark>Description</mark>: ' . $issue,
            '  <mark>Further Details</mark>: ' . $url
        ]);
    }

    protected function checkWithNPM(): bool
    {
        if (!file_exists('package-lock.json') || !$this->shell->commandExists('npm')) {
            return false;
        }

        $response = json_decode(
            $this->shell->runCommand('npm', 'audit', '--json', '--production')['stdout'],
            true
        );

        foreach ($response['vulnerabilities'] as $packageVulnerabilities) {
            foreach ($packageVulnerabilities['via'] as $vulnerability) {
                $this->reportVulnerability(
                    $vulnerability['name'],
                    $vulnerability['title'],
                    $vulnerability['url'],
                    $vulnerability['severity']
                );
            }
        }

        $this->appendSummary('npm');
        return true;
    }
}