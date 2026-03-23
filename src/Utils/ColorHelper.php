<?php

declare(strict_types=1);

namespace FP\CartRecovery\Utils;

/**
 * Helper per colori esadecimali.
 */
final class ColorHelper {

    private const DEFAULT_HEX = '#667eea';

    /**
     * Sanitizza una stringa come colore esadecimale (#RGB o #RRGGBB).
     */
    public static function sanitize_hex(string $color): string {
        $color = trim($color);
        if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color)) {
            return $color;
        }
        return self::DEFAULT_HEX;
    }
}
