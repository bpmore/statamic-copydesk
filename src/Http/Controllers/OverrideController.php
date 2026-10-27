<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Http\Controllers;

use Bpmore\Copydesk\Edition;
use Bpmore\Copydesk\Gate\OverridePasses;
use Bpmore\Copydesk\Gate\PublishGate;
use Illuminate\Http\Request;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Data;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

/**
 * "Publish anyway": someone allowed to says why, and gets a pass for their
 * next publish of this page, good for ten minutes.
 *
 * The reason is required and has to say something: three words at least.
 * It is written down with the page and the rules when the publish goes
 * through, and shown in the site report.
 */
class OverrideController extends CpController
{
    public const MIN_WORDS = 3;

    public function __invoke(Request $request, OverridePasses $passes): array
    {
        abort_unless(Edition::isPro(), 403, 'Publishing past the gate is part of Copydesk Pro.');

        $user = User::current();
        abort_unless($user?->can(PublishGate::OVERRIDE), 403, 'Your role cannot publish past the Copydesk gate.');

        $reason = trim((string) $request->input('reason'));
        abort_if(str_word_count($reason) < self::MIN_WORDS, 422, 'Say why this page should publish as it is, in a few words.');

        $reference = (string) $request->input('reference');
        $entry = null;
        $collection = (string) $request->input('collection');

        if ($reference !== '') {
            $entry = Data::find($reference);
            abort_if(! $entry instanceof EntryContract, 404, 'That entry could not be found.');
            $this->authorize('edit', $entry);
            $collection = (string) $entry->collection()?->handle();
        } else {
            $found = $collection === '' ? null : Collection::findByHandle($collection);
            abort_if($found === null, 404, 'Say which collection this page is in.');
            $this->authorize('create', [EntryContract::class, $found]);
        }

        $passes->grant((string) $user->id(), $entry?->id(), $collection, $reason);

        return ['message' => 'Press Publish again within '.OverridePasses::MINUTES.' minutes. Your reason is kept with the page.'];
    }
}
