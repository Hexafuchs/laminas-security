<?php

namespace Hexafuchs\LaminasSecurity\Enums;

enum CheckState
{
    case FAILED;
    case WARNED;
    case SUCCEEDED;
    case SKIPPED;

    public function format(): string
    {
        return match ($this) {
            self::FAILED    => '<bold-fail>Failed</bold-fail>',
            self::WARNED    => '<bold-warn>Warned</bold-warn>',
            self::SUCCEEDED => '<bold-success>Succeeded</bold-success>',
            self::SKIPPED   => '<bold-skip>Skipped</bold-skip>',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::FAILED    => '<fail>✕ </>',
            self::WARNED    => '<warn>⚠ </>',
            self::SUCCEEDED => '<success>✓ </>',
            self::SKIPPED   => '<skip>? </>',
        };
    }

    public static function formats(): array
    {
        $formats = [];

        foreach (self::cases() as $case) {
            $formats[] = $case->format();
        }

        return $formats;
    }
}
