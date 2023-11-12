<?php

namespace Hexafuchs\LaminasSecurity\Checks\Webserver;

use Hexafuchs\LaminasSecurity\Checks\AbstractCheck;
use Laminas\Http\Client;

abstract class AbstractWebserverCheck extends AbstractCheck
{
    /**
     * Useragent that should be used for the client
     */
    const USERAGENT = 'laminas-security-scanner';

    /**
     * @var \Laminas\Http\Client|null
     */
    private ?Client $client = null;

    /**
     * @param string|null $baseUrl
     */
    public function __construct(
        protected readonly ?string $baseUrl
    ) {}

    /**
     * @inheritDoc
     */
    public function getReportName(): string
    {
        return 'webserver';
    }

    /**
     * Creates a new Client or returns a previously created one.
     *
     * @return \Laminas\Http\Client
     */
    protected function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client($this->baseUrl, [
                'useragent' => self::USERAGENT
            ]);
        }

        return $this->client;
    }

    /**
     * Test if the baseUrl from the configuration is valid.
     *
     * @return bool
     */
    protected function validateBaseUrl(): bool
    {
        if (empty($this->baseUrl)) {
            $this->fail([
                '<fail>Could not run check due to missing configuration. Please provide your applications url in ' .
                '<mark>config/autoload/local.php["laminas-security"]["app"]["base_url"]</mark> before running this check</fail>',
                'Valid Example: <success>https://example.com</success>'
            ]);

            return false;
        } else if (filter_var($this->baseUrl, FILTER_VALIDATE_URL) === false) {
            $this->fail([
                '<fail>Invalid Application-URL</fail> at <mark>config["laminas-security"]["app"]["base_url"]</mark>. ' .
                'Please make sure you put a valid url including scheme into this field before running this check',
                'Current value: <fail>' . $this->baseUrl . '</fail>',
                'Valid Example: <success>https://example.com</success>'
            ]);

            return false;
        } else {
            return true;
        }
    }
}