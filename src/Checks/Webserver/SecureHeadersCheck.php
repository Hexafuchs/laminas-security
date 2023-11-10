<?php

namespace Hexafuchs\LaminasSecurity\Checks\Webserver;

use Hexafuchs\LaminasSecurity\Enums\HttpHeader;
use Laminas\Http\Client\Exception\RuntimeException;
use Laminas\Http\Request;
use Laminas\Http\Response;

class SecureHeadersCheck extends AbstractWebserverCheck
{
    /**
     * @var \Laminas\Http\Request|null $request
     */
    protected ?Request $request = null;

    /**
     * @var \Laminas\Http\Response|null $response
     */
    protected ?Response $response = null;

    /**
     * @inheritDoc
     */
    public function getCheckName(): string
    {
        return 'Webserver sets important headers';
    }

    /**
     * @inheritDoc
     */
    protected function run(): void
    {
        if ($this->validateBaseUrl()) {
            $this->sendRequest($this->baseUrl);

            if ($this->response !== null) {
                $this->checkResponse();
            }

            $this->finish();
        }
    }

    protected function sendRequest(string $url): void
    {
        $this->request = (new Request())
            ->setMethod(Request::METHOD_GET)
            ->setUri($url);

        try {
            $this->response = $this->getClient()->send($this->request);
        } catch (RuntimeException $e) {
            $this->fail($e->getMessage());
        }
    }

    protected function checkResponse(): void
    {
        if ($this->request->getUri()->getScheme() !== 'https') {
            $this->warn([
                '- Using <warn>insecure request</warn>, please consider switching to <success>https</success>.',
                "  If you need a certificate, you can get one for free from LetsEncrypt (https://letsencrypt.org/getting-started/)"
            ]);
        }

        $this->checkContentSecurityPolicy();
        $this->checkHstsHeader();
        $this->checkPermissionsPolicy();
        $this->checkReferrerPolicy();
        $this->checkServerFingerprint();
        $this->checkContentSniffingHeader();
        $this->checkClickjackingHeader();
    }

    protected function checkContentSecurityPolicy(): void
    {
        $header = $this->response->getHeaders()->get(HttpHeader::CONTENT_SECURITY_POLICY->value);

        if ($header === false) {
            $header = $this->response->getHeaders()->get(HttpHeader::CONTENT_SECURITY_POLICY_REPORT_ONLY->value);

            if ($header !== false) {
                $this->insecureHeader(
                    HttpHeader::CONTENT_SECURITY_POLICY_REPORT_ONLY,
                    'Content-Security-Policy is in Report-Only mode and therefore <warn>not enforced</warn>.'
                );
            }
        }

        if ($header === false) {
            $this->missingHeader(HttpHeader::CONTENT_SECURITY_POLICY);
        } else {
            if (
                !str_contains($header->getFieldValue(), 'default-src') &&
                !str_contains($header->getFieldValue(), 'script-src')
            ) {
                $this->insecureHeader(
                    HttpHeader::CONTENT_SECURITY_POLICY,
                    'Content-Security-Policy neither sets default-src or script-src, which leads to possible unwanted execution of JS.',
                    $header->getFieldValue()
                );
            }
            if (
                str_contains($header->getFieldValue(), 'unsafe-eval') ||
                str_contains($header->getFieldValue(), 'unsafe-inline')
            ) {
                $this->insecureHeader(
                    HttpHeader::CONTENT_SECURITY_POLICY,
                    'Content-Security-Policy contains unsafe-Rules, which leads to possible unwanted execution of JS.',
                    $header->getFieldValue()
                );
            }
        }
    }

    protected function insecureHeader(HttpHeader $header, string $reason = null, string $value = null): void
    {
        $messages   = [];
        $messages[] = sprintf('- Insecure Header <warn>%s</warn>:', $header->value);

        if ($reason !== null) {
            $messages[] = '  Reason: ' . $reason;
        }

        if ($value !== null) {
            $messages[] = '  Current value: ' . $value;
        }

        $messages[] = sprintf('  (For further details visit: %s)', $header->getLink());
        $this->warn($messages);
    }

    protected function missingHeader(HttpHeader $header): void
    {
        $this->fail([
            sprintf('- Missing Header <fail>%s</fail>', $header->value),
            sprintf('  (For details visit: %s)', $header->getLink())
        ]);
    }

    protected function checkHstsHeader(): void
    {
        if ($this->request->getUri()->getScheme() === 'https') {
            $header = $this->response->getHeaders()->get(HttpHeader::STRICT_TRANSPORT_SECURITY->value);

            if ($header === false) {
                $this->missingHeader(HttpHeader::STRICT_TRANSPORT_SECURITY);
            }
        }
    }

    protected function checkPermissionsPolicy(): void
    {
        $header = $this->response->getHeaders()->get(HttpHeader::PERMISSIONS_POLICY->value);

        if ($header === false) {
            $this->missingHeader(HttpHeader::PERMISSIONS_POLICY);
        }
    }

    protected function checkReferrerPolicy(): void
    {
        $header = $this->response->getHeaders()->get(HttpHeader::REFERRER_POLICY->value);

        if ($header === false) {
            $this->missingHeader(HttpHeader::REFERRER_POLICY);
        } else {
            if ($header->getFieldValue() === 'unsafe-url') {
                $this->insecureHeader(
                    HttpHeader::REFERRER_POLICY,
                    'Your current policy could leak private information to insecure or malicious servers.',
                    $header->getFieldValue()
                );
            }
        }
    }

    protected function checkServerFingerprint(): void
    {
        $header = $this->response->getHeaders()->get(HttpHeader::SERVER->value);

        if ($header !== false) {
            if (preg_match('/[0-9]+(\.[0-9]+)+/', $header->getFieldValue()) === 1) {
                $this->insecureHeader(
                    HttpHeader::SERVER,
                    'Server-Header should not include the version-Number of your webserver',
                    $header->getFieldValue()
                );
            }
        }
    }

    protected function checkContentSniffingHeader(): void
    {
        $header = $this->response->getHeaders()->get(HttpHeader::X_CONTENT_TYPE_OPTIONS->value);

        if ($header === false) {
            $this->missingHeader(HttpHeader::X_CONTENT_TYPE_OPTIONS);
        } else if ($header->getFieldValue() !== 'nosniff') {
            $this->insecureHeader(
                HttpHeader::X_CONTENT_TYPE_OPTIONS,
                'Value should be "nosniff"',
                $header->getFieldValue()
            );
        }
    }

    protected function checkClickjackingHeader(): void
    {
        $header = $this->response->getHeaders()->get(HttpHeader::X_FRAME_OPTIONS->value);

        if ($header === false) {
            $this->missingHeader(HttpHeader::X_FRAME_OPTIONS);
        } else if (str_starts_with($header->getFieldValue(), 'ALLOW-FROM')) {
            $this->insecureHeader(
                HttpHeader::X_FRAME_OPTIONS,
                'ALLOW-FROM is deprecated and should no longer be used.',
                $header->getFieldValue()
            );
        }
    }
}