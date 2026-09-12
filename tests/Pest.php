<?php

declare(strict_types=1);

use Bpmore\Plain\Tests\TestCase;

// Only the feature tests boot Statamic. The engine takes text and returns
// findings, and its suite, in packages/readability-core, boots nothing.
uses(TestCase::class)->in('Feature');
