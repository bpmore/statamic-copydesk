<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style;

use LogicException;

/**
 * The runnable rule for a definition, by its type.
 */
final class RuleFactory
{
    public static function make(RuleDefinition $definition): StyleRule
    {
        return match ($definition->type) {
            RuleType::WordList => new Rules\WordList($definition),
            default => throw new LogicException("Rules of type [{$definition->type->value}] are not built yet."),
        };
    }
}
