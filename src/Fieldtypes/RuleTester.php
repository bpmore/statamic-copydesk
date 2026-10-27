<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Fieldtypes;

use Statamic\Fields\Fieldtype;

/**
 * "Try it on some text", in the rule editor's sidebar: the pack as the form
 * has it now, run over text the editor types, before anything is saved.
 * Stores nothing.
 */
class RuleTester extends Fieldtype
{
    protected static $handle = 'copydesk_rule_tester';

    protected static $title = 'Copydesk rule tester';

    protected $selectable = false;

    public function preProcess($data)
    {
        return null;
    }

    public function process($data)
    {
        return null;
    }

    public function preload()
    {
        return ['url' => request()->route('name') ? cp_route('copydesk.packs.try', request()->route('name')) : null];
    }
}
