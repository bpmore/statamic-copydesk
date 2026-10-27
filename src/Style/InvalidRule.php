<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style;

use InvalidArgumentException;

/**
 * A rule in a pack that cannot be used as written.
 *
 * The message names the pack and the rule, and says what to change, because
 * the person reading it is editing a YAML file and needs to find the line:
 * `house.yaml, rule "no-em-dash": "severity" is "critical"; use info,
 * warning or error.` A rule that is quietly skipped is worse than one that
 * stops the load with a sentence.
 */
final class InvalidRule extends InvalidArgumentException
{
    public static function in(string $source, string $rule, string $problem): self
    {
        return new self("{$source}, rule {$rule}: {$problem}");
    }
}
