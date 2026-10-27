<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Gate;

use Illuminate\Filesystem\Filesystem;

/**
 * Every publish that went through the gate on someone's say-so: who, which
 * page, which rules, and the reason they gave. Kept under the app's storage,
 * one JSON object per line, newest last, and shown in the site report, so
 * an override is never a problem nobody can see.
 */
final class OverrideLog
{
    public function __construct(private readonly Filesystem $files, private readonly string $path) {}

    public static function inStorage(Filesystem $files): self
    {
        return new self($files, storage_path('statamic/copydesk/overrides.jsonl'));
    }

    /**
     * @param  array{entry: string, title: string, collection: string, site: string, user: string, user_name: string, reason: string, rules: list<string>}  $override
     */
    public function record(array $override): void
    {
        $this->files->ensureDirectoryExists(dirname($this->path));
        $this->files->append($this->path, json_encode([...$override, 'at' => now()->toIso8601String()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    /**
     * Every override, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        if (! $this->files->exists($this->path)) {
            return [];
        }

        $lines = array_filter(explode("\n", (string) $this->files->get($this->path)), static fn (string $line): bool => trim($line) !== '');
        $all = array_values(array_filter(array_map(static fn (string $line): mixed => json_decode($line, true), $lines), is_array(...)));

        return array_reverse($all);
    }
}
