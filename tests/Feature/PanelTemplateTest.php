<?php

declare(strict_types=1);

it('ships a panel template Vue can compile', function () {
    $script = (string) file_get_contents(__DIR__.'/../../resources/js/plain-panel.js');

    preg_match('/template: `(.*?)`,\s*\}\);/s', $script, $m);

    expect($m[1] ?? '')->not->toBe('');
    assertVueTemplateIsWellFormed($m[1]);
    expect($script)->toContain("Statamic.\$components.register('readability_panel-fieldtype'");
    expect($script)->toContain("cp_url('plain/check')");
});
