<?php

namespace App\View\Components;

class Initials
{
    /** First + last initials of a person name, honoring Dr./Prof. titles. */
    public static function for(?string $name): string
    {
        $parts = preg_split('/\s+/', trim($name ?? '')) ?: [];
        $parts = array_values(array_filter($parts, fn ($p) => $p !== '' && ! preg_match('/^(Dr\.|Prof\.|Mr\.|Ms\.|Mrs\.)$/i', $p)));

        if ($parts === []) {
            return '?';
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = $parts[count($parts) - 1];

        return mb_strtoupper($first.(count($parts) > 1 ? mb_substr($last, 0, 1) : ''));
    }
}
