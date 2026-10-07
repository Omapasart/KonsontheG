<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RegistrationWizard
{
    public const SESSION_KEY = 'registration_wizard';

    /**
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        return session(self::SESSION_KEY, []);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function merge(array $values): void
    {
        session([self::SESSION_KEY => array_merge(self::data(), $values)]);
    }

    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public static function reached(): int
    {
        return (int) (self::data()['reached'] ?? 1);
    }

    public static function markReached(int $step): void
    {
        self::merge(['reached' => max(self::reached(), $step)]);
    }

    public static function canAccess(int $step): bool
    {
        return $step <= max(self::reached(), 1);
    }

    public static function storeTempFile(UploadedFile $file, string $kind): string
    {
        $directory = 'registrations/tmp/'.self::fileKey();
        $oldPath = self::data()[$kind.'_path'] ?? null;

        if (is_string($oldPath) && Storage::disk('local')->exists($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $name = $kind.'-'.Str::uuid().'.'.$extension;

        return $file->storeAs($directory, $name, 'local');
    }

    public static function ownsTempPath(?string $path): bool
    {
        if (! is_string($path) || $path === '') {
            return false;
        }

        $normalized = str_replace('\\', '/', $path);
        $prefix = 'registrations/tmp/'.self::fileKey().'/';

        return str_starts_with($normalized, $prefix)
            && ! str_contains($normalized, '..')
            && Storage::disk('local')->exists($normalized);
    }

    private static function fileKey(): string
    {
        $key = self::data()['file_key'] ?? null;

        if (! is_string($key) || $key === '') {
            $key = Str::uuid()->toString();
            self::merge(['file_key' => $key]);
        }

        return $key;
    }
}
