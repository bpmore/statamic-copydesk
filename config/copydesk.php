<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | On or off
    |---------------------------------------------------------------------------
    */

    'enabled' => env('COPYDESK_ENABLED', true),

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
    | Targets by collection
    |---------------------------------------------------------------------------
    |
    | A different grade for a collection, with the tolerance above: patient
    | education might be written for grade 6 while news is written for 8.
    | Collections not named here use the target above.
    |
    */

    'targets' => [
        // 'patient_education' => 6,
    ],

    /*
    |---------------------------------------------------------------------------
    | Paragraphs well above the target
    |---------------------------------------------------------------------------
    |
    | A page can be on target while one paragraph is far past it. A paragraph
    | is marked when its own grade starts more than `above_by` grades above
    | the top of the target. Short paragraphs are left alone, because the
    | formulas mean little on a few sentences: `min_words` is the fewest
    | words a paragraph needs before its grade is trusted.
    |
    */

    'paragraphs' => [
        'enabled' => true,
        'above_by' => 3,
        'min_words' => 40,
    ],

    /*
    |---------------------------------------------------------------------------
    | House style packs
    |---------------------------------------------------------------------------
    |
    | Which packs are on. Null turns on Copydesk's own core-punctuation,
    | plain-language and inclusive-language packs, and every pack in
    | resources/copydesk. A list turns on exactly those packs, by name, so
    | news-style-starter, for one, is on only when it is listed. The packs
    | and how to write your own are in the docs.
    |
    */

    'packs' => null,

    /*
    |---------------------------------------------------------------------------
    | Fields
    |---------------------------------------------------------------------------
    |
    | Which fields are the page's prose, by blueprint handle. '*' is every
    | blueprint; a named blueprint adds to it. Bard, markdown, textarea and
    | text fields are read, and so are the Bard, markdown and textarea fields
    | nested in a listed Grid, Replicator, Group or Bard set. A nested text
    | field (a caption) is not. Anything else on the list is ignored.
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
        // The site's own word list, kept on the settings screen. A word with
        // nothing to say instead is an error; others take this severity.
        // The starter list is the plain-language pack.
        'jargon' => ['severity' => 'warning'],
        'nominalization' => ['severity' => 'tip'],
        'filler' => ['severity' => 'tip'],
    ],

    /*
    |---------------------------------------------------------------------------
    | Gate
    |---------------------------------------------------------------------------
    |
    | Copydesk Pro. Off by default. When on, publishing a page in one of these
    | collections (all of them, if none is named) is refused while it has a
    | finding whose severity is 'error': a very long sentence, a word on the
    | site's list with nothing to say instead, or any rule a pack marks as an
    | error. Never on a grade: a grade band is a heuristic, and a press
    | release stopped for scoring 11 is how a checker gets uninstalled.
    | Drafts always save. Someone with "override copydesk gate" can publish
    | anyway, once, giving a reason that is kept in the site report.
    |
    */

    'gate' => [
        'enabled' => false,
        'collections' => [],
    ],

    /*
    |---------------------------------------------------------------------------
    | Checking from the command line
    |---------------------------------------------------------------------------
    |
    | `php please copydesk:scan` grades every published page and can stop a
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
