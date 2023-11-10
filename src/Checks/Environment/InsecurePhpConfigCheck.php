<?php

namespace Hexafuchs\LaminasSecurity\Checks\Environment;

use Hexafuchs\LaminasSecurity\Enums\CheckState;

class InsecurePhpConfigCheck extends AbstractEnvironmentCheck
{

    /**
     * @inheritDoc
     */
    public function getCheckName(): string
    {
        return 'Configuration within php.ini is secure';
    }

    /**
     * @inheritDoc
     */
    protected function run(): void
    {
        if ($this->convertIniValue('short_open_tag')) {
            $this->warn([
                '- <warn>short_open_tag</warn> is enabled.',
                '  This could result in issues with XML-Files and should therefore be disabled.'
            ]);
        }

        if (!$this->convertIniValue('zend.exception_ignore_args')) {
            $this->fail([
                '- <fail>zend.exception_ignore_args</fail> is disabled.',
                '  This could expose sensitive information within stack traces.'
            ]);
        }

        if ($this->convertIniValue('zend.exception_string_param_max_len') > 0) {
            $this->fail([
                '- <fail>zend.exception_string_param_max_len</fail> is greater than 0.',
                '  This could expose sensitive information within stack traces.'
            ]);
        }

        if ($this->convertIniValue('expose_php')) {
            $this->warn([
                '- <warn>expose_php</warn> is enabled.',
                '  This exposes the existence of PHP on your server to potential attackers.'
            ]);
        }

        if ($this->convertIniValue('display_errors')) {
            $this->fail([
                '- <fail>display_errors</fail> is enabled.',
                '  This exposes sensitive information and errors to your users and should therefore be disabled.'
            ]);
        }

        if ($this->convertIniValue('display_startup_errors')) {
            $this->fail([
                '- <fail>display_startup_errors</fail> is enabled.',
                '  This exposes sensitive information and errors to your users and should therefore be disabled.'
            ]);
        }

        if (!$this->convertIniValue('log_errors')) {
            $this->fail([
                '- <fail>log_errors</fail> is disabled.',
                '  This forbids logging errors to a log file which prevents monitoring of issues in your application.'
            ]);
        }

        if ($this->convertIniValue('ignore_repeated_errors')) {
            $this->warn([
                '- <warn>ignore_repeated_errors</warn> is enabled.',
                '  This hides errors that occur multiple times from appearing in your logs.'
            ]);
        }

        if ($this->convertIniValue('allow_url_fopen')) {
            $this->fail([
                '- <fail>allow_url_fopen</fail> is enabled.',
                '  This allows <cmd-warn> fopen() </cmd-warn> to access urls like ' .
                '<cmd-warn> http:// </cmd-warn> and <cmd-warn> ftp:// </cmd-warn>',
                '  and therefore could download malicious files and execute them.'
            ]);
        }

        if ($this->convertIniValue('allow_url_include')) {
            $this->fail([
                '- <fail>allow_url_include</fail> is enabled.',
                '  This allows <cmd-warn> include </cmd-warn> and <cmd-warn> require </cmd-warn> to access urls like ' .
                '<cmd-warn> http:// </cmd-warn> and <cmd-warn> ftp:// </cmd-warn>',
                '  and therefore could download malicious files and execute them.'
            ]);
        }

        $this->appendSummary();
        $this->finish();
    }

    protected function convertIniValue(string $key): string|int|bool|null
    {
        $value = ini_get($key);

        if ($value === false) {
            return null;
        }

        if (is_numeric($value)) {
            return intval($value);
        }

        if (in_array(strtolower($value), ['1', 'on', 'yes', 'true'])) {
            return true;
        }

        if (in_array(strtolower($value), ['0', 'off', 'no', 'false'])) {
            return false;
        }

        return $value;
    }

    protected function appendSummary(): void
    {
        if (in_array($this->getState(), [CheckState::WARNED, CheckState::FAILED])) {
            $this->appendDetails([
                '',
                'Issues with your <warn>PHP configuration</warn> detected.',
                'Make sure to review your php.ini and change the values above.',
                'To find files which set the above values you can use the <cmd-warn> php --ini </cmd-warn> command.',
                '',
                'Additionally check your <warn>webserver</warn> and <warn>PHP-FPM</warn> configurations if available,',
                'sometimes they overwrite values from your <mark>php.ini</mark>-Files.'
            ]);
        }
    }
}