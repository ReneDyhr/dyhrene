<?php

/**
 * @coversNothing
 */
it('redirects to login when not authenticated', function () {
    $response = $this->get('/');

    $response->assertStatus(302);
    $response->assertRedirect(route('login'));
});
