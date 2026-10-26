# Plan: Copydesk and A11y Gate, one publish check

**Status:** draft for Brent. Nothing in A11y Gate changes until he says so.
**Date:** 2026-10-26
**Read:** `statamic-a11y-gate` at `e7614af` (local copy, same as `origin/main`), `statamic-plain` at `872b700`.

## The problem

Both add-ons stop a publish today, each in its own way, and neither knows the other exists.

| | A11y Gate | Plain 1.0 |
|---|---|---|
| Listens on | `EntrySaving` | `EntrySaving` |
| Looks at | The rendered page (HTML), fetched on save | The fields the author wrote |
| On by default | Yes, every collection, mode `refuse` | No, opt-in per collection |
| If the check itself breaks | Refuses the save (fails closed) | Lets the save through and logs it (fails open) |
| Refusal | `ValidationException`, key `a11y_gate` | `ValidationException`, key `plain` |
| Panel reads | `errors.a11y_gate` | `errors.plain` |
| Way to add checks from outside | None. `CheckPack::CHECKS` is a fixed list | n/a |

What a site with both sees (inferred from the code, not yet run on greenhouse): the first listener to throw stops the save. The author sees only that add-on's list, fixes it, publishes again, and only then gets refused by the other one. Which one goes first depends on the order Statamic finds the listeners in, which nobody chose.

A11y Gate's checks also don't fit Copydesk. Its `Check` interface reads a parsed HTML page (`DOMXPath`). Plain's rules read fields, so they can point at the exact field and Bard node. Squeezing Plain into a DOM `Check` would lose that, and would hard-code Plain into A11y Gate.

## Options

1. **A11y Gate takes outside checks (recommended).** A11y Gate adds a small contract that other add-ons sign up to with a tag, the way Site Weather does. A11y Gate runs its own checks and every signed-up one, then refuses once with everything listed. Plain uses it when it is there and keeps its own gate when it is not.
2. **Keep two gates, change nothing.** Free. The author still gets refused twice, one at a time.
3. **Make Plain a DOM check inside A11y Gate.** Loses field-level pointers, forces Plain's fail-open rule into a fail-closed gate, and makes A11y Gate depend on Plain's classes. Not recommended.

## Option 1 in detail

### In A11y Gate (a minor release, nothing removed)

1. Add `Bpmore\A11yGate\Contracts\GateContributor` with the tag `a11y-gate.contributors`:
   - `key(): string`: a stable slug, also used as the error key (`copydesk`).
   - `label(): string`: "Copydesk", for the refusal list.
   - `inspect(Entry $entry): ContributedResult`.
2. `ContributedResult` carries: the lines to show, whether they block, and "could not check." The contributor decides its own rules: which collections it gates, whether a broken check blocks, whether an override applies. A11y Gate does not second-guess them.
3. `RefuseUnlessAccessible` collects its own result plus every contributor's, then throws **one** `ValidationException` with one key per source: `a11y_gate` for its own lines, `copydesk` for Copydesk's. Each panel already reads its own key, so neither panel's code needs to change to show the refusal.
4. A contributor that throws is caught and logged, and does not break the save. (Same promise Site Weather makes.)
5. A11y Gate's `warn` mode covers only A11y Gate's own checks. A contributor that blocks still blocks.
6. Tests: a fake contributor that blocks, passes, cannot check, and throws. One refusal lists both sources.

### In Copydesk (Plain's code, renamed)

1. `CopydeskGateContributor` wraps the existing `PublishGate` and turns its result into a `ContributedResult`. It keeps Plain 1.0's rules: drafts never blocked, never on a grade, fails open, override with a logged reason.
2. Sign it up by tag name as a string, so Copydesk never requires A11y Gate.
3. `RefuseUnlessPlain` (the name kept: "refuse unless the page is plain") steps aside only when `interface_exists(GateContributor::class)`. An older A11y Gate without the contract means Copydesk keeps its own gate, so nothing is ever left ungated.
4. Tests for both cases: with the contract (Copydesk's listener does nothing, contributor answers) and without it (today's behavior).

### Publish anyway, with a reason (decided 2026-10-26: one way that works for both)

One override flow, owned by whichever gate is running: A11y Gate's combined gate when it is installed, Copydesk's own gate when it is not. Each add-on keeps its own permission and its own say over whether its problems can be overridden.

**Permissions**

- Copydesk: `override copydesk gate`, as the spec names it.
- A11y Gate: new `override accessibility gate`.
- Nobody but super admins has either by default, per CONVENTIONS.

**Who can be overridden**

- Every result says whether it can be overridden, and which permission it takes.
- Copydesk's problems: overridable by anyone with `override copydesk gate`.
- A11y Gate's own problems: **not overridable unless the site turns it on** (`allow_override`, default `false`). A11y Gate refuses today with no way around it, and a site that installed it for that reason keeps it that way after the upgrade.
- An override only clears what the user is allowed to clear. If a page has a Copydesk problem and an accessibility problem, and the user only has `override copydesk gate`, the publish is still refused, and the message says the accessibility problem still has to be fixed.

**How the reason gets to the gate**

1. The refusal in the panel shows a "Publish anyway" button, only to users who may override something on the list.
2. The button asks for a reason (required, at least a few words) and sends it to a CP endpoint. The endpoint checks the permission, records the reason, and stores a single-use pass for this entry and this user. The pass expires after 10 minutes.
3. The author presses Publish again. The gate finds the pass, uses it up, clears what the pass covers, and lets the save through.

Why a pass and not a field on the form: Statamic sends form values with an ordinary save, but a collection with revisions publishes through a different request that carries only a message (`EntriesController::update` read in `statamic/cms` v6.38.0 on greenhouse; the revisions path not yet traced). A pass works for both, and for scripts.

**Where the reason is kept**

- A11y Gate passes each overridden source its share: `recordOverride(Entry, User, reason, findings)` on the contributor.
- Copydesk stores it in its own data under `storage/`, and shows it in its site report (Copydesk acceptance test 4).
- A11y Gate writes its own share to its log, with entry, user and reason.

**Contract additions** (on top of the list above)

- `ContributedResult::overridable(): bool` and `overridePermission(): ?string`.
- `GateContributor::recordOverride(...)`.

### On greenhouse

1. Install both. Publish a page with one accessibility problem and one blocked word.
2. Expected: one refusal, both lists, each in its own panel.
3. Run the same with A11y Gate removed, then with an old A11y Gate. Copydesk must still block on its own.
4. Override: a user with only `override copydesk gate` is still refused for the accessibility problem. After it is fixed, they publish with a reason, and the reason shows in Copydesk's report.
5. Override on a collection with revisions turned on.

## Rough size

- A11y Gate change with tests: about 1 to 2 days.
- Override flow, both sides, with tests: about 1 more day.
- Copydesk side with tests: about half a day.
- Greenhouse run: about 2 hours.
- These are guesses from the size of the code, not measured.

## Open questions

1. **Who shows the combined list?** Each panel shows its own part. Is that enough, or should A11y Gate's panel show a single "2 add-ons stopped this publish" line?
2. **The Copydesk spec says "register as a Gate check instead of a second gate."** Under this plan Copydesk still has its own gate when A11y Gate is missing. That reads as the intent, but it is worth saying out loud.

## Checked vs assumed

- Checked by reading code: both listeners, both refusal keys, both panels' error keys, A11y Gate's fixed check list, its default mode and collections.
- Assumed, unchecked: the "refused twice in a row" behavior. It follows from how Laravel stops at the first thrown exception, but it has not been run with both add-ons installed.
- Checked by reading code: an ordinary CP save sends form values in the request (`statamic/cms` v6.38.0).
- Assumed, unchecked: what the revisions publish request carries. The pass design does not depend on it.
- Assumed, unchecked: the time estimates.
