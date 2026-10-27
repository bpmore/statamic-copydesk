<?php

declare(strict_types=1);

use Bpmore\Copydesk\Edition;
use Bpmore\Copydesk\Tests\TestCase;

// Only the feature tests boot Statamic. The engine takes text and returns
// findings, and its suite, in packages/readability-core, boots nothing.
uses(TestCase::class)->in('Feature');

/** Run this test as a site on Copydesk Pro. */
function pro(): void
{
    config()->set('statamic.editions.addons.'.Edition::PACKAGE, 'pro');
}

/**
 * A Bard value as Statamic stores it: paragraphs of plain text, with a
 * heading where a string starts with "# ".
 *
 * @return list<array<string, mixed>>
 */
function bard(string ...$paragraphs): array
{
    return array_map(static function (string $text): array {
        if (str_starts_with($text, '# ')) {
            return ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => substr($text, 2)]]];
        }

        return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
    }, $paragraphs);
}

/**
 * Whether markup Vue will compile is well formed.
 *
 * The panel's template becomes `defineComponent({ template })` in the
 * browser, where an unclosed tag is a compile error that shows as a blank
 * sidebar and never as an exception on the server. Vue's template syntax
 * is close enough to XML that parsing it as XML catches the mistake here.
 */
function assertVueTemplateIsWellFormed(string $html): void
{
    // The one void element Blade emits, made self-closing for the parser.
    $html = (string) preg_replace('/<input([^>]*?)\s*\/?>/', '<input$1 />', $html);

    // A comparison inside an attribute value ("v-if" with a ">") would end
    // the tag early for the rewrite below. Vue never sees this copy.
    $html = (string) preg_replace_callback('/="([^"]*)"/', fn ($m) => '="'.str_replace(['<', '>'], ['&lt;', '&gt;'], $m[1]).'"', $html);

    // Vue's `:prop` and `@event` are not XML names, and a bare boolean
    // attribute has no value. Both are rewritten so the parser accepts them;
    // Vue never sees this copy.
    $xml = preg_replace_callback('/<[a-z][\w-]*(\s[^<>]*)?\/?>/i', function ($m) {
        return preg_replace_callback(
            '/(\s)([:@]?)([a-z][\w:.-]*)(="[^"]*")?/i',
            fn ($a) => $a[1].($a[2] === ':' ? 'bind-' : ($a[2] === '@' ? 'on-' : '')).$a[3].($a[4] ?? '="'.$a[3].'"'),
            $m[0],
        );
    }, $html);
    $xml = str_replace('&', '&amp;', (string) $xml);

    $previous = libxml_use_internal_errors(true);
    $ok = simplexml_load_string('<root>'.$xml.'</root>') !== false;
    $errors = array_map(fn ($e) => trim($e->message).' (line '.$e->line.')', libxml_get_errors());
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    expect($ok)->toBeTrue('the template is not well formed, so Vue would not compile it: '.implode('; ', $errors));
}

/**
 * A utility page's text, flattened. The control panel is an Inertia
 * application, so a utility's HTML arrives inside a JSON page object where
 * every newline is a literal backslash-n and the markup is entity-encoded.
 */
function utilityText(string $html): string
{
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $html = (string) preg_replace_callback('/\\\\u([0-9a-f]{4})/i', fn ($m) => mb_chr((int) hexdec($m[1])), $html);   // the JSON page object's escapes

    return (string) preg_replace('/\s+/', ' ', str_replace(['\\n', '\\t', '\\r', '\\/'], [' ', ' ', ' ', '/'], $html));
}
