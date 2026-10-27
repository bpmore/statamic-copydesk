<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Listeners;

use Bpmore\Copydesk\Gate\A11yGateBridge;
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
 *
 * With A11y Gate installed (a version that takes contributors), this stands
 * aside: A11y Gate asks Copydesk during the same save and refuses once, with
 * both lists. With an A11y Gate too old for that, or none, this is the gate.
 */
final class RefuseUnlessPlain
{
    public function __construct(
        private readonly PublishGate $gate,
        private readonly LoggerInterface $log,
    ) {}

    public function handle(EntrySaving $event): void
    {
        // A11y Gate asks Copydesk itself, in its own listener, and refuses
        // once with both lists. Two listeners would refuse twice.
        if (A11yGateBridge::joined()) {
            return;
        }

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

        throw ValidationException::withMessages(['copydesk' => $result->lines()])->status(422);
    }
}
