# Copydesk in the editor

How the checks as you type work, and how to see them working on a real site.

## What the author sees

- **Bard:** the flagged words are underlined. The style says how much it matters, so colour is
  never the only sign: dotted for a tip, wavy for something to look at, double for something to
  fix. Click an underline, or put the caret in it and press Option+Enter (Alt+Enter on Windows),
  for a box with the message, a button for each suggestion, and "Ignore here". Escape closes the
  box and puts the caret back. A screen reader hears what an underline says when the caret
  reaches it.
- **Markdown, textarea and text fields:** the same issues as a list under the field. In a
  textarea or text field each suggestion is a button; in markdown it is shown as text, because
  markdown is rendered before it is checked and the place in the source is not known.
- **The readability panel:** "As you type" counts the issues on the form by severity, and lists
  each one as a button that puts the caret on its words.

Each field asks the server 600 ms after the author stops typing, with that field's text only.

## Which fields are checked

The ones named in `fields` in `config/copydesk.php`, and any Bard, markdown or textarea field
nested inside one of them: a Bard in a Replicator set inside a Grid named there is checked. A
text field nested inside one (a caption, a button label) is not.

## Checking it on a site

A blueprint with every kind of field, for a collection named `copydesk_check`:

```yaml
title: 'Copydesk check'
tabs:
  main:
    sections:
      -
        fields:
          - handle: title
            field: { type: text, required: true }
          - handle: subtitle
            field: { type: text }
          - handle: summary
            field: { type: textarea }
          - handle: notes
            field: { type: markdown }
          - handle: content
            field:
              type: bard
              sets:
                main:
                  sets:
                    callout:
                      fields:
                        - handle: text
                          field: { type: bard }
          - handle: sections
            field:
              type: grid
              fields:
                - handle: blocks
                  field:
                    type: replicator
                    sets:
                      main:
                        sets:
                          prose:
                            fields:
                              - handle: body
                                field: { type: bard }
                              - handle: caption
                                field: { type: text }
```

And in `config/copydesk.php`:

```php
'fields' => [
    '*' => ['content'],
    'copydesk_check' => ['subtitle', 'summary', 'notes', 'sections'],
],
```

Then, on an entry:

1. Type "Plain words — short ones." in Content. The dash is underlined, wavy, within a second.
2. Click it. The box offers "Use “,”". Choose it: the text reads "Plain words, short ones."
3. Put the caret in "utilize" and press Option+Enter. Focus moves to the box. Escape returns it.
4. Type an em dash in the Body inside Sections. It is underlined too.
5. In the readability panel, press "Show “—” in Body". The caret lands on the dash.
6. Type "We utilize it." in Summary. The list under it offers "Use “use”".
7. Type in Caption. Nothing is listed: a nested text field is not checked.
