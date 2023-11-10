<?php

namespace Hexafuchs\LaminasSecurity\Enums;

enum HttpHeader: string
{
    case CONTENT_SECURITY_POLICY             = 'Content-Security-Policy';
    case CONTENT_SECURITY_POLICY_REPORT_ONLY = 'Content-Security-Policy-Report-Only';
    case PERMISSIONS_POLICY                  = 'Permissions-Policy';
    case REFERRER_POLICY                     = 'Referrer-Policy';
    case SERVER                              = 'Server';
    case STRICT_TRANSPORT_SECURITY           = 'Strict-Transport-Security';
    case X_CONTENT_TYPE_OPTIONS              = 'X-Content-Type-Options';
    case X_FRAME_OPTIONS                     = 'X-Frame-Options';

    function getLink(): string
    {
        return match ($this) {
            self::CONTENT_SECURITY_POLICY             => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy',
            self::CONTENT_SECURITY_POLICY_REPORT_ONLY => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy-Report-Only',
            self::PERMISSIONS_POLICY                  => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Permissions-Policy',
            self::REFERRER_POLICY                     => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referrer-Policy',
            self::SERVER                              => 'https://crashtest-security.com/server-version-fingerprinting/',
            self::STRICT_TRANSPORT_SECURITY           => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security',
            self::X_CONTENT_TYPE_OPTIONS              => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options',
            self::X_FRAME_OPTIONS                     => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options',
        };
    }
}
