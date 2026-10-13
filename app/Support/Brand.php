<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Per-deployment brand identity (logo text). Stored in storage (gitignored)
 * so the same codebase can ship as DEMO POS, Load Cafe, etc. without
 * committing brand-specific values.
 */
class Brand
{
    private const FILE = 'brand_name.txt';

    public static function name(): string
    {
        try {
            if (Storage::disk('local')->exists(self::FILE)) {
                $name = trim((string) Storage::disk('local')->get(self::FILE));
                if ($name !== '') {
                    return $name;
                }
            }
        } catch (\Throwable) {
            // fall through to the config default
        }

        return (string) config('app.name', 'DEMO POS');
    }

    public static function setName(?string $name): void
    {
        $name = trim((string) $name);

        if ($name === '') {
            Storage::disk('local')->delete(self::FILE);
        } else {
            Storage::disk('local')->put(self::FILE, $name);
        }
    }
}
