<?php

declare(strict_types=1);

\dataset('user', fn() => [App\Models\User::factory()->create()]);

\dataset('receipt', fn() => [App\Models\Receipt::factory()->create()]);
