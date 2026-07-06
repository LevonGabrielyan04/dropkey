<?php

test('landing page can be rendered', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSuccessful()
        ->assertSee('<link rel="icon" href="/favicon.png" type="image/png">', false)
        ->assertSee('End-to-end encrypted messaging', false)
        ->assertSee('Private messages.', false)
        ->assertSee('Zero-knowledge relay', false)
        ->assertSee('Frequently asked questions', false)
        ->assertSee('class="scroll-smooth scroll-pt-24"', false);
});
