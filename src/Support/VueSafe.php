<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Support;

/**
 * Text for a view that Vue will compile.
 *
 * The utility view and the widget view are not printed as HTML: Statamic
 * hands the rendered markup to `DynamicHtmlRenderer`, which does
 * `defineComponent({ template: html })`. So Blade's escaping is only half
 * the job. A value containing `{{`, which a page title, a dictionary term,
 * an override's reason or an error message can, would be read by Vue as an
 * expression and run in the browser of whoever opened the page, as them.
 * Vue finds its delimiters in the raw source before it decodes entities, so
 * a brace written as an entity is text and nothing more.
 *
 * This is for text and for plain attributes. It is not enough for a prop a
 * component renders with `v-html`, such as `text` on `ui-description` and
 * `heading` on `ui-card-panel`: Vue decodes the entities in an attribute
 * before the component sees them, and `v-html` then writes the result as
 * markup. A dynamic value never goes into one of those; it goes into the
 * component's slot, or into a plain element, as text.
 */
final class VueSafe
{
    public static function text(mixed $value): string
    {
        return str_replace(['{', '}'], ['&#123;', '&#125;'], e((string) $value));
    }
}
