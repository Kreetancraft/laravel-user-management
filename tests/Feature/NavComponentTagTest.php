<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

/**
 * Rendering the view the way a host's published copy is rendered.
 *
 * Nothing did this before 0.11.0 — the suite exercised Navigation::grouped() and
 * the Nav class directly, so a view that only works when the class supplies its
 * data went out and took a host's dashboard down with an undefined $sections.
 */
it('renders the nav through the tag a host actually writes', function (): void {
    expect(Blade::render('<x-user-management::nav />'))->toBeString();
});

it('renders when nothing hands it any data', function (): void {
    // Exactly what a published copy hits when the anonymous component path
    // resolves it instead of the class: no $sections, no $items.
    expect(View::make('user-management::components.nav')->render())->toBeString();
});
