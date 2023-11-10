<?php

namespace Hexafuchs\LaminasSecurity\Checks\Webserver;

use Hexafuchs\LaminasSecurity\Enums\CheckState;
use Laminas\Http\Client\Exception\RuntimeException;
use Laminas\Http\Request;
use Laminas\Http\Response;

class ForbiddenFileAccessCheck extends AbstractWebserverCheck
{
    /**
     * @var array $files
     */
    protected array $files = [];

    /**
     * @inheritDoc
     */
    public function getCheckName(): string
    {
        return 'Webserver does not serve sensible files';
    }

    /**
     * @inheritDoc
     */
    protected function run(): void
    {
        if (!$this->validateBaseUrl()) {
            return;
        }

        try {
            $this->runAllTests();
            $this->appendSummary();
        } catch (RuntimeException $e) {
            $this->fail($e->getMessage());
        } finally {
            $this->cleanup();
        }

        $this->finish();
    }

    protected function runAllTests(): void
    {
        // Files in Root-Directory should never be accessible
        [$filename, $content] = $this->placeFile('.');
        $this->testRequest(
            $filename, $content,
            '- Files in <fail>Project-Root</fail> are accessible using <cmd-mark> /{filename} </cmd-mark>'
        );
        $this->testRequest(
            '../' . $filename, $content,
            '- Files in <fail>Project-Root</fail> are accessible using <cmd-mark> /../{filename} </cmd-mark>'
        );

        // Files in Config-Directory should never be accessible
        [$filename, $content] = $this->placeFile('config');
        $this->testRequest(
            'config/' . $filename, $content,
            '- <fail>Configuration-Folder</fail> is accessible using <cmd-mark> /config/{filename} </cmd-mark>'
        );
        $this->testRequest(
            '../config/' . $filename, $content,
            '- <fail>Configuration-Folder</fail> is accessible using <cmd-mark> /../config/{filename} </cmd-mark>'
        );

        // Files in Public-Directory should only be accessible using Root-Path
        [$filename, $content] = $this->placeFile('public');
        $this->testRequest($filename, $content,
            '- <fail>Public-Files</fail> are not accessible using <cmd-mark> /{filename} </cmd-mark>',
            true, true
        );
        $this->testRequest(
            'public/' . $filename, $content,
            '- <fail>Public-Files</fail> are accessible using <cmd-mark> /public/{filename} </cmd-mark>',
            false, true
        );
    }

    protected function appendSummary(): void
    {
        if (in_array($this->getState(), [CheckState::WARNED, CheckState::FAILED])) {
            $this->appendDetails([
                '',
                'Issues with your <warn>webserver configuration</warn> detected.',
                'Make sure that your webserver uses the <mark>public</mark>-Folder as Webroot and ' .
                'does not allow file traversal using <mark>/../</mark>',
                'Consult the documentation of your webserver for further information'
            ]);
        }
    }

    protected function cleanup(): void
    {
        foreach ($this->files as $file) {
            unlink($file);
        }
    }

    protected function testRequest(
        string       $requestUrl,
        string       $content,
        string|array $messages,
        bool         $shouldMatch = false,
        bool         $warn = false
    ): void
    {
        $response = $this->sendRequest($requestUrl);

        if (str_contains($response->getBody(), $content)) {
            if (!$shouldMatch) {
                $this->appendResult($warn, $messages);
            }
        } else if ($shouldMatch) {
            $this->appendResult($warn, $messages);
        }
    }

    protected function appendResult(bool $warn, array|string $messages): void
    {
        if ($warn) {
            $this->warn($messages);
        } else {
            $this->fail($messages);
        }
    }

    protected function sendRequest(string $path): Response
    {
        $request = (new Request())
            ->setUri($this->baseUrl . '/' . $path)
            ->setMethod(Request::METHOD_GET);

        return $this->getClient()->send($request);
    }

    protected function placeFile(string $directory): array
    {
        do {
            $filename = sprintf(
                'laminas-security-check-%s-%s.txt',
                date('Ymd-His'),
                $this->generateRandomString(6)
            );
        } while (file_exists($directory . DIRECTORY_SEPARATOR . $filename));

        $content = $this->generateRandomString(32);
        file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, $content);
        $this->files[] = $directory . DIRECTORY_SEPARATOR . $filename;

        return [$filename, $content];
    }

    /**
     * Generates completely random Strings in the given length.
     *
     * WARNING: THIS IS NOT CRYPTOGRAPHICALLY SECURE!!! DO NOT USE IT FOR PASSWORDS OR ANYTHING LIKE THAT!!!
     *
     * @param int $length
     * @return string
     */
    protected function generateRandomString(int $length): string
    {
        $result = '';
        $chars  = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $max    = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[rand(0, $max)];
        }

        return $result;
    }
}