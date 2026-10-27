<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style;

use InvalidArgumentException;

/**
 * A pack, or a set of packs, that cannot be used as written: a key a pack
 * does not have, a pack that extends one that does not exist, two packs
 * that extend each other. The message names the files involved.
 */
final class InvalidPack extends InvalidArgumentException {}
