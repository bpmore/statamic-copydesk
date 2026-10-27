<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style;

/**
 * Where a rule applies: which collections, and which fields within them.
 * `*` is every one, and is the default for both.
 */
final class Scope
{
    public const ANY = '*';

    /**
     * @param  list<string>  $collections
     * @param  list<string>  $fields
     */
    public function __construct(
        public readonly array $collections = [self::ANY],
        public readonly array $fields = [self::ANY],
    ) {}

    public function covers(string $collection, string $field): bool
    {
        return self::matches($this->collections, $collection) && self::matches($this->fields, $field);
    }

    /**
     * @param  list<string>  $list
     */
    private static function matches(array $list, string $value): bool
    {
        return in_array(self::ANY, $list, true) || in_array($value, $list, true);
    }
}
