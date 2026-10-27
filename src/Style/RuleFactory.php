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
            RuleType::Term => new Rules\Term($definition),
            RuleType::Pattern => new Rules\Pattern($definition),
            RuleType::Punctuation => new Rules\Punctuation($definition),
            RuleType::Casing => new Rules\Casing($definition),
            default => throw new LogicException("Rules of type [{$definition->type->value}] are not built yet."),
        };
    }
}
