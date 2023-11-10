<?php

namespace Hexafuchs\LaminasSecurity\Checks\Code;

use Hexafuchs\LaminasSecurity\Services\ShellExecutor;

class TaintAnalysisCheck extends AbstractCodeCheck
{
    public function __construct(
        protected readonly ShellExecutor $shell
    ) {}

    /**
     * @inheritDoc
     */
    public function getCheckName(): string
    {
        return 'Taint Analysis does not find issues';
    }

    /**
     * @inheritDoc
     */
    protected function run(): void
    {
        if (!$this->shell->commandExists('vendor/bin/psalm')) {
            $this->appendDetails([
                'Cannot find <skip>psalm</skip> executable. Please make sure to require psalm using:',
                '<cmd-warn> composer require --dev <cmd-skip>vimeo/psalm</cmd-skip> </cmd-warn>'
            ]);

            return;
        }

        $taintResult = $this->shell->runCommand(
            'vendor/bin/psalm',
            '--taint-analysis',
            '--output-format=json'
        );

        switch ($taintResult['exit_code']) {
            case 0:
                $this->success();
                break;
            case 2:
                $this->appendSummary($taintResult['stdout']);
                break;
            default:
                $this->warn([
                    'There was an issue running <warn>psalm</warn>, you can execute it yourself using:',
                    '<cmd-warn> vendor/bin/psalm --taint-analysis </cmd-warn>'
                ]);
        }
    }

    protected function appendSummary(string $response): void
    {
        $findings = json_decode($response, true);

        foreach ($findings as $finding) {
            $this->fail(sprintf('- [<fail>%s</fail>] %s', strtoupper($finding['severity']), $finding['message']));

            if ($finding['line_from'] === $finding['line_to']) {
                $this->fail(sprintf(
                    '  at <mark>%s</mark> in line <warn>%d</warn>',
                    $finding['file_name'],
                    $finding['line_from']
                ));
            } else {
                $this->fail(sprintf(
                    '  at <mark>%s</mark> in lines <warn>%d</warn>-<warn>%d</warn>',
                    $finding['file_name'],
                    $finding['line_from'],
                    $finding['line_to']
                ));
            }
        }

        $this->fail([
            '',
            'Execute <cmd-warn> vendor/bin/psalm <cmd-success>--taint-analysis</cmd-success> </cmd-warn> for further details.'
        ]);
    }
}