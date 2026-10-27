<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Http\Controllers;

use Bpmore\Copydesk\Edition;
use Bpmore\Copydesk\Packs\PackForm;
use Bpmore\Copydesk\Packs\PackLibrary;
use Bpmore\Copydesk\Style\InvalidPack;
use Bpmore\Copydesk\Style\InvalidRule;
use Bpmore\Copydesk\Style\Pack;
use Bpmore\Copydesk\Style\PackSet;
use Bpmore\ReadabilityCore\Extraction\PlainTextExtractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Statamic\CP\PublishForm;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Symfony\Component\Yaml\Yaml;

/**
 * The rule editor (Pro): a site's own packs, edited in a form, written as
 * YAML in resources/copydesk.
 *
 * A pack is checked as Copydesk will read it before it is written: the
 * same rules, the same messages, against every other pack, so a save
 * can never leave a file that stops the site's checks. Saving rewrites the
 * file, so comments in it are not kept; the form says so.
 */
class PacksController extends CpController
{
    public const PERMISSION = 'manage copydesk rules';

    private const NAME = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function store(Request $request, PackLibrary $library): RedirectResponse
    {
        $this->allow();

        $name = trim((string) $request->input('name'));

        if (preg_match(self::NAME, $name) !== 1) {
            throw ValidationException::withMessages(['name' => 'A pack name is lowercase letters, numbers and hyphens, like "house".']);
        }

        if (in_array($name, [...$library->bundledNames(), ...$library->siteNames()], true)) {
            throw ValidationException::withMessages(['name' => "There is already a pack called \"{$name}\". Pick another name, or edit that one."]);
        }

        $this->write($library, $name, ['description' => 'Our house style.', 'extends' => ['core-punctuation'], 'rules' => []]);

        return redirect(cp_route('copydesk.packs.edit', $name));
    }

    public function edit(string $name, PackLibrary $library): PublishForm
    {
        $this->allow();
        $yaml = $this->existing($library, $name);

        return PublishForm::make(PackForm::blueprint($this->others($library, $name)))
            ->title('House style: '.$name)
            ->icon('fieldtype-textarea')
            ->values(PackForm::toValues($yaml))
            ->submittingTo(cp_route('copydesk.packs.update', $name));
    }

    public function update(Request $request, string $name, PackLibrary $library): array
    {
        $this->allow();
        $existing = $this->existing($library, $name);
        $values = PublishForm::make(PackForm::blueprint($this->others($library, $name)))->submit($request->all());
        $yaml = PackForm::fromValues($values, $existing);

        $this->check($library, $name, $yaml);
        $this->write($library, $name, $yaml);

        return ['saved' => true];
    }

    /**
     * The pack as the form has it now, run over some text: what it would
     * flag, before anything is saved.
     */
    public function try(Request $request, string $name, PackLibrary $library): array
    {
        $this->allow();

        $yaml = PackForm::fromValues((array) $request->input('values', []), $this->existing($library, $name));
        $set = $this->check($library, $name, $yaml);
        $document = (new PlainTextExtractor)->extract((string) $request->input('text', ''));
        $found = [];

        foreach ($set->runnable([$name]) as $rule) {
            foreach ($rule->check($document) as $finding) {
                $found[] = ['rule' => $finding->rule, 'severity' => $finding->severity->value, 'message' => $finding->message, 'excerpt' => $finding->excerpt, 'suggestion' => $finding->suggestion];
            }
        }

        return ['findings' => $found];
    }

    private function allow(): void
    {
        abort_unless(Edition::isPro(), 403, 'The rule editor is part of Copydesk Pro.');
        abort_unless(User::current()?->can(self::PERMISSION), 403, 'Your role cannot edit house style rules.');
    }

    /**
     * Checked the way Copydesk will read it, against every other pack.
     *
     * @param  array<string, mixed>  $yaml
     */
    private function check(PackLibrary $library, string $name, array $yaml): PackSet
    {
        try {
            $set = $library->setWith(Pack::fromArray($name, $yaml, "resources/copydesk/{$name}.yaml"));
            $set->rules([$name]);

            return $set;
        } catch (InvalidPack|InvalidRule $e) {
            throw ValidationException::withMessages(['rules' => $e->getMessage()]);
        }
    }

    /** @return array<string, mixed> */
    private function existing(PackLibrary $library, string $name): array
    {
        abort_unless(preg_match(self::NAME, $name) === 1 && in_array($name, $library->siteNames(), true), 404, "There is no site pack called \"{$name}\". The packs Copydesk ships cannot be edited; start one of your own that extends them.");

        $yaml = Yaml::parseFile($library->sitePath($name));

        return is_array($yaml) ? $yaml : [];
    }

    /** @return list<string> */
    private function others(PackLibrary $library, string $name): array
    {
        return array_values(array_diff([...$library->bundledNames(), ...$library->siteNames()], [$name]));
    }

    /** @param array<string, mixed> $yaml */
    private function write(PackLibrary $library, string $name, array $yaml): void
    {
        $path = $library->sitePath($name);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, Yaml::dump($yaml, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE));
    }
}
