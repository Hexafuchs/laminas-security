<?php

namespace Hexafuchs\LaminasSecurity\Checks\Environment;

use AssertionError;
use Hexafuchs\LaminasSecurity\ConfigProvider;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Throwable;

class InsecurePasswordsCheck extends AbstractEnvironmentCheck
{
    /**
     * URL to the HaveIBeenPwnedAPI to check hashes against
     */
    protected const HAVE_I_BEEN_PWNED_API_URI = 'https://api.pwnedpasswords.com/range/';

    /**
     * @var bool $checkAgainstHIBP
     */
    protected bool $checkAgainstHIBP;

    /**
     * @var array $haveIBeenPwnedCache
     */
    protected array $haveIBeenPwnedCache = [];

    /**
     * @var \Laminas\Http\Client $client
     */
    protected Client $client;

    /**
     * @var array $config
     */
    protected array $config;

    /**
     * @var string $secretParamsRegex
     */
    protected string $secretParamsRegex;

    /**
     * @var array $secrets
     */
    protected array $secrets;

    /**
     * @var array $passwordRequirements
     */
    protected array $passwordRequirements;

    public function __construct(array $config)
    {
        $this->client               = new Client();
        $this->config               = $config;
        $this->secretParamsRegex    = $config[ConfigProvider::LAMINAS_SECURITY_CONFIG]['secrets']['secret_params_regex'];
        $this->checkAgainstHIBP     = $config[ConfigProvider::LAMINAS_SECURITY_CONFIG]['secrets']['use_hibp_api'];
        $this->passwordRequirements = [
            'length'    => $config[ConfigProvider::LAMINAS_SECURITY_CONFIG]['secrets']['require_length'],
            'uppercase' => $config[ConfigProvider::LAMINAS_SECURITY_CONFIG]['secrets']['require_uppercase'],
            'lowercase' => $config[ConfigProvider::LAMINAS_SECURITY_CONFIG]['secrets']['require_lowercase'],
            'numerical' => $config[ConfigProvider::LAMINAS_SECURITY_CONFIG]['secrets']['require_numerical'],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getCheckName(): string
    {
        return 'Configured Passwords are secure';
    }

    /**
     * @inheritDoc
     */
    protected function run(): void
    {
        $this->checkBranch($this->config, 'config');
        $this->appendDetails([
            sprintf('Found <success>%d</success> secret(s) in configuration', count($this->secrets)),
            '',
            'Checking all found secrets with the following criteria:',
            sprintf('- at least <success>%d</success> character(s) long', $this->passwordRequirements['length']),
            sprintf('- contains at least <success>%d</success> uppercase letter(s)', $this->passwordRequirements['uppercase']),
            sprintf('- contains at least <success>%d</success> lowercase letter(s)', $this->passwordRequirements['lowercase']),
            sprintf('- contains at least <success>%d</success> number(s)', $this->passwordRequirements['numerical']),
            '',
            'You can change these settings at <mark>config["laminas-security"]["secrets"]</mark> by adjusting the ' .
            'parameters <mark>require_length</mark>, <mark>require_uppercase</mark>, <mark>require_lowercase</mark> ' .
            'and <mark>require_numerical</mark>',
            ''
        ]);

        $this->checkIfSecretsMeetRequirements();

        if ($this->checkAgainstHIBP) {
            try {
                $this->appendDetails([
                    'Checking all found secrets against the <mark>HaveIBeenPwned-Database</mark>...',
                    ''
                ]);
                $this->checkHaveIBeenPwned();
            } catch (Throwable $e) {
                $this->warn([
                    '<fail>Aborted</fail> HaveIBeenPwned-Lookup due to the following error:',
                    $e->getMessage()
                ]);
            }
        } else {
            $this->appendDetails([
                'Checking against https://haveibeenpwned.com/ is disabled.',
                'If you want to enable it, you can set ' .
                '<mark>config["laminas-security"]["secrets"]["use_hibp_api"]</mark> to <success>true</success>',
                'If you decide to enable it, <mark>HaveIBeenPwned</mark> will only receive an ' .
                '<success>anonymized</success> version of your secret!',
                'You can read more at https://www.troyhunt.com/ive-just-launched-pwned-passwords-version-2/'
            ]);
        }

        $this->finish();
    }

    protected function checkBranch(array $branch, string $path): void
    {
        foreach ($branch as $key => $twig) {
            $twigPath = $this->appendPath($path, $key);

            if ($this->matchesSensitiveParamsName($key)) {
                $this->inspectParam($twig, $twigPath);
            }

            if (is_array($twig)) {
                $this->checkBranch($twig, $twigPath);
            }
        }
    }

    protected function appendPath(string $path, string $key): string
    {
        return sprintf('%s["%s"]', $path, $key);
    }

    protected function matchesSensitiveParamsName(int|string $key): bool
    {
        return preg_match($this->secretParamsRegex, $key) === 1;
    }

    protected function inspectParam(mixed $twig, string $path): void
    {
        if (is_array($twig)) {
            foreach ($twig as $key => $value) {
                $this->inspectParam($value, $this->appendPath($path, $key));
            }
        }

        // Don't inspect class-names, they aren't secrets
        if (is_string($twig) && class_exists($twig)) {
            return;
        }

        $this->secrets[$path] = $twig;
    }

    protected function checkIfSecretsMeetRequirements(): void
    {
        foreach ($this->secrets as $path => $secret) {
            $errors = [];

            if ($this->passwordRequirements['length'] > strlen($secret)) {
                $errors[] = sprintf(
                    '- at least <fail>%d</fail> character(s) long',
                    $this->passwordRequirements['length']
                );
            }

            if ($this->passwordRequirements['uppercase'] > preg_match_all('/[A-Z]/', $secret)) {
                $errors[] = sprintf(
                    '- contains at least <fail>%d</fail> uppercase letter(s)',
                    $this->passwordRequirements['uppercase']
                );
            }

            if ($this->passwordRequirements['lowercase'] > preg_match_all('/[a-z]/', $secret)) {
                $errors[] = sprintf(
                    '- contains at least <fail>%d</fail> lowercase letter(s)',
                    $this->passwordRequirements['lowercase']
                );
            }

            if ($this->passwordRequirements['numerical'] > preg_match_all('/[0-9]/', $secret)) {
                $errors[] = sprintf(
                    '- contains at least <fail>%d</fail> number(s)',
                    $this->passwordRequirements['numerical']
                );
            }

            if (!empty($errors)) {
                $this->fail([
                    sprintf('The secret at <fail>%s</fail> does not match the following criteria:', $path),
                    ...$errors,
                    ''
                ]);
            }
        }
    }

    protected function checkHaveIBeenPwned(): void
    {
        foreach ($this->secrets as $path => $secret) {
            $count = $this->getOccurrencesInHaveIBeenPwnedDatabase($secret);

            if ($count > 0) {
                $this->warn(sprintf(
                    'The secret at <warn>%s</warn> was found <mark>%d</mark> times in the HaveIBeenPwned-Database',
                    $path, $count
                ));
            }
        }
    }

    protected function getOccurrencesInHaveIBeenPwnedDatabase(string $secret): int
    {
        $hashedSecret     = strtoupper(sha1($secret));
        $anonymizedSecret = substr($hashedSecret, 0, 5);
        $remainingSecret  = substr($hashedSecret, 5);

        if (!array_key_exists($anonymizedSecret, $this->haveIBeenPwnedCache)) {
            $uri = self::HAVE_I_BEEN_PWNED_API_URI . $anonymizedSecret;

            // Make REALLY (!!!) sure we only send the anonymized password
            if (strlen($uri) !== strlen(self::HAVE_I_BEEN_PWNED_API_URI) + 5) {
                throw new AssertionError(
                    'This message should <bold-fail>NEVER</bold-fail> appear! ' .
                    'If you see this, something went <bold-fail>horribly wrong</bold-fail> while trying to execute ' .
                    'the <mark>HaveIBeenPwned-Database-Lookup</mark>! The lookup was <fail>aborted</fail> before ' .
                    'anything could be leaked to a third party.' . PHP_EOL .
                    '<warn>Further Details</warn>:' . PHP_EOL .
                    'The <mark>HaveIBeenPwned-Database-Lookup</mark> requires the user to send the ' .
                    '<warn>first 5 characters</warn> of his <success>hashed secret</success>. This ' .
                    'message appears only if, for some unknown reason, the generated URI contained ' .
                    '<warn>more than 5 characters</warn> of your <success>hashed secret</success>.'
                );
            }

            $response = $this->client->send((new Request())
                ->setUri($uri)
                ->setMethod(Request::METHOD_GET));

            if ($response->getStatusCode() === 200) {
                $this->haveIBeenPwnedCache[$anonymizedSecret] = explode("\n", $response->getBody());
            } else {
                $this->haveIBeenPwnedCache[$anonymizedSecret] = [];
            }
        }

        foreach ($this->haveIBeenPwnedCache[$anonymizedSecret] as $line) {
            if (str_starts_with(strtoupper($line), $remainingSecret)) {
                [, $count] = explode(':', $line, 2);
                return $count;
            }
        }

        return 0;
    }
}