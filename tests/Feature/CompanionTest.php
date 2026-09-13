<?php

declare(strict_types=1);

use Bpmore\Plain\Companion;
use Statamic\Addons\Manifest;
use Statamic\Facades\Addon;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\User;

/**
 * The one line for a site that has Accessibility Report installed too, so
 * that nobody meeting both takes one of them for a mistake.
 */
beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Main', 'locale' => 'en_US', 'url' => '/']]);
    Collection::make('pages')->save();

    $this->user = User::make()->makeSuper();
    $this->user->save();
});

/** Accessibility Report, as Statamic would list it once Composer has installed it. */
function installA11yReport(): void
{
    app(Manifest::class)->manifest[Companion::A11Y_REPORT] = [
        'id' => Companion::A11Y_REPORT,
        'slug' => 'statamic-a11y-report',
        'version' => '1.2.0',
        'namespace' => 'Bpmore\\A11yReport',
        'autoload' => 'src/',
        'provider' => 'Bpmore\\A11yReport\\ServiceProvider',
    ];

    // The facade memoises the list it built from the manifest at boot.
    Addon::clearResolvedInstance();
}

it('says nothing when Accessibility Report is not installed', function () {
    expect(Companion::a11yReportInstalled())->toBeFalse();
    expect(Companion::notice())->toBeNull();

    $page = utilityText($this->actingAs($this->user)->get(cp_route('utilities.index').'/plain')->assertOk()->getContent());

    expect($page)->not->toContain('Accessibility Report is installed');
});

it('says, once and in one line, who owns which surface when it is', function () {
    installA11yReport();

    expect(Companion::a11yReportInstalled())->toBeTrue();

    $page = utilityText($this->actingAs($this->user)->get(cp_route('utilities.index').'/plain')->assertOk()->getContent());

    expect(substr_count($page, 'Accessibility Report is installed as well.'))->toBe(1);
    expect($page)->toContain('grades every page as served on its own scheduled scan');
    expect($page)->toContain('this page, the publish-form panel and the gate are Plain');
    expect($page)->toContain('two readings of the same site, not two scans of the same thing');

    // And the notice compiles where the page does, in each of its states.
    assertVueTemplateIsWellFormed(utilityHtml($this->user));
});

/** The utility's own markup, as Vue will receive it: unpacked from Inertia's page object. */
function utilityHtml($user): string
{
    $response = test()->actingAs($user)->get(cp_route('utilities.index').'/plain')->assertOk()->getContent();

    preg_match('/data-page="([^"]+)"/', $response, $m);
    $page = json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);

    return (string) $page['props']['html'];
}
