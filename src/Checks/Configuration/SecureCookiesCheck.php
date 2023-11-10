<?php

namespace Hexafuchs\LaminasSecurity\Checks\Configuration;

class SecureCookiesCheck extends AbstractConfigurationCheck
{
    /**
     * @param array $config
     */
    public function __construct(
        protected readonly array $config
    ) {}

    /**
     * @inheritDoc
     */
    public function getCheckName(): string
    {
        return 'Session-Cookies are secure';
    }

    /**
     * @inheritDoc
     */
    protected function run(): void
    {
        if (!in_array('Laminas\Session\Container', $this->config['session_containers'] ?? [])) {
            $this->appendDetails('<mark>laminas-session</mark> is not active');
            return;
        }

        $sessionConfig = $this->config['session_config'] ?? [];

        if (($sessionConfig['cookie_httponly'] ?? false) !== true) {
            $this->fail([
                '- Session cookie should be <fail>HTTP-Only</fail>',
                '  you can enable this by setting <mark>config["session_config"]["cookie_httponly"]</mark> to <success>true</success>'
            ]);
        }

        if (($sessionConfig['cookie_secure'] ?? false) !== true) {
            if (str_starts_with($this->config['laminas-security']['app']['url'] ?? '', 'https://')) {
                $this->fail([
                    '- Session cookie should be <fail>Secure</fail> if your app is using <success>https</success>',
                    '  you can enable this by setting <mark>config["session_config"]["cookie_secure"]</mark> to <success>true</success>'
                ]);
            } else {
                $this->warn([
                    '- Session cookie should be <warn>Secure</warn> if your app is using https',
                    '  you can enable this by setting <mark>config["session_config"]["cookie_secure"]</mark> to <success>true</success>'
                ]);
            }
        }

        $this->finish();
    }
}