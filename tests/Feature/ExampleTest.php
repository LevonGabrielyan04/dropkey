<?php

test('landing page can be rendered', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSuccessful()
        ->assertSee('Share passwords.', false)
        ->assertSee('Zero-knowledge secret sharing', false)
        ->assertSee('Frequently asked questions', false);
});
