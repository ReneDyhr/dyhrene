<?php

declare(strict_types=1);

\it('redirects to login when not authenticated', function () {
    $response = $this->get('/');

    $response->assertStatus(302);
    $response->assertRedirect(\route('login'));
});
