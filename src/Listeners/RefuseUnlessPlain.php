<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Listeners;

use Bpmore\Copydesk\Gate\GateResult;
use Bpmore\Copydesk\Gate\PublishGate;
use Illuminate\Validation\ValidationException;
use Psr\Log\LoggerInterface;
use Statamic\Events\EntrySaving;
use Statamic\Facades\User;

/**
 * The gate. Statamic binds this to `EntrySaving` from the type hint on
 * `handle()`, so there is no registration to keep in step with the class.
 *
 * It throws rather than returning false, for the reason Accessibility Gate
 * found the hard way: returning false stops the save without saying why,
 * and the author is told the editor is broken. A ValidationException comes
 * back as a 422 the control panel already handles: `errors` go onto the
 * publish container, keyed as sent, and the panel draws `errors.copydesk` in
 * full. The toast says "The given data was invalid." whatever we write
 * here (`Statamic\Exceptions\ValidationException::summarize()` overrides
 * Laravel's), so the lines are written to be read as a list in the panel.
 *
 * When the check itself cannot run, the save goes through and the log
 * says so. That is the opposite of Accessibility Gate, on purpose: a page
 * that will not render is a problem with the page, but a readability check
 * that throws is a problem with this addon, and this addon must never be
 * the reason a site cannot publish.
 */
final class RefuseUnlessPlain
{
    public function __construct(
        private readonly PublishGate $gate,
        private readonly LoggerInterface $log,
    ) {}

    public function handle(EntrySaving $event): void
    {
        $result = $this->gate->inspect($event->entry, User::current());

        if ($result->couldNotBeChecked()) {
            $this->log->error(
                'Copydesk could not check this entry before it was saved, so it was saved unchecked: '.$result->reason,
                ['entry' => $event->entry->id()],
            );

            return;
        }

        if (! $result->shouldRefuse()) {
            return;
        }

        throw ValidationException::withMessages(['copydesk' => $this->lines($result)])->status(422);
    }

    /**
     * The refusal, as the author reads it: what stopped the publish, each
     * thing to fix with its field, and a reminder that nothing else did.
     *
     * @return list<string>
     */
    private function lines(GateResult $result): array
    {
        $count = count($result->blocking);

        $lines = [
            'This page was not published. '.($count === 1 ? 'One thing has to be fixed first.' : "{$count} things have to be fixed first."),
        ];

        foreach ($result->blocking as $finding) {
            $field = $finding->location === null ? null : ($result->report?->blockFields[$finding->location->block] ?? null);
            $lines[] = $finding->message.($field === null ? '' : " (in {$field})");
        }

        $lines[] = 'The grade never stops a publish, and neither do warnings or tips; they are in the Readability panel.';

        return $lines;
    }
}
