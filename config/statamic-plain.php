<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | On or off
    |---------------------------------------------------------------------------
    */

    'enabled' => env('PLAIN_ENABLED', true),

    /*
    |---------------------------------------------------------------------------
    | Locales
    |---------------------------------------------------------------------------
    |
    | Every formula behind the score was built on English, and on any other
    | language it returns a confident number that means nothing. A site whose
    | locale is not listed here is not scored, and the panel says why. A bare
    | language ("en") allows every region of it; a full locale ("en_US") only
    | itself.
    |
    */

    'locales' => ['en'],

    /*
    |---------------------------------------------------------------------------
    | Target
    |---------------------------------------------------------------------------
    |
    | The reading level you write for, and how far either side of it is fine.
    | Grade 8 with a tolerance of 1 is "Grade 7–9", which is where most plain-
    | language guidance for the public lands.
    |
    */

    'target' => ['grade' => 8, 'tolerance' => 1],

    /*
    |---------------------------------------------------------------------------
    | Fields
    |---------------------------------------------------------------------------
    |
    | Which fields are the page's prose, by blueprint handle. '*' is every
    | blueprint; a named blueprint adds to it. Only Bard, markdown, textarea
    | and text fields are read; anything else on the list is ignored.
    |
    */

    'fields' => [
        '*' => ['content'],
    ],

    /*
    |---------------------------------------------------------------------------
    | Exclusions
    |---------------------------------------------------------------------------
    |
    | Text that should not count. Code, quotations, reference lists, addresses
    | and tables of figures are left out; so is anything inside a Bard set or
    | an HTML class named here. Proper names come out before grading, as the
    | reading-level criterion asks, and stay in for the rules.
    |
    */

    'exclude' => [
        'bard_sets' => ['code', 'citation'],
        'html_classes' => ['no-readability'],
        'code' => true,
        'blockquotes' => true,
        'references' => true,
        'addresses' => true,
        'figures' => true,
        'proper_names' => true,
    ],

    /*
    |---------------------------------------------------------------------------
    | Rules
    |---------------------------------------------------------------------------
    |
    | Each rule's options and severity. A rule not listed runs with its
    | defaults; 'enabled' => false leaves one out. The very-long limit follows
    | the long one at twice its value unless set.
    |
    */

    'rules' => [
        'long_sentence' => ['words' => 25, 'severity' => 'warning'],
        'very_long_sentence' => ['severity' => 'error'],
        'long_paragraph' => ['words' => 150, 'sentences' => 8, 'severity' => 'warning'],
        'passive_voice' => ['max_percent' => 20, 'severity' => 'warning'],
        'unexpanded_acronym' => ['severity' => 'warning'],
        'jargon' => ['dictionary' => 'default', 'severity' => 'warning'],
        'nominalization' => ['severity' => 'tip'],
        'filler' => ['severity' => 'tip'],
    ],

    /*
    |---------------------------------------------------------------------------
    | Gate
    |---------------------------------------------------------------------------
    |
    | Off by default, and even on it blocks only on the hard rules named in
    | 'block_on', never on a grade. A grade band is a heuristic; a press
    | release stopped for scoring 11 is how a checker gets uninstalled.
    |
    */

    'gate' => [
        'enabled' => false,
        'collections' => [],
        'block_on' => ['very_long_sentence', 'jargon'],
    ],

    /*
    |---------------------------------------------------------------------------
    | Checking from the command line
    |---------------------------------------------------------------------------
    |
    | `php please plain:check` grades every published page and can stop a
    | build. 'fail_above' is how many pages may read above the target before
    | it exits non-zero: a number of pages ("10") or a share ("10%"). Null
    | never fails on the grade; --fail-on-errors is separate and off unless
    | asked for.
    |
    */

    'check' => [
        'fail_above' => null,
    ],

];
