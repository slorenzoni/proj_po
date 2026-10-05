<?php

use App\Models\Categoria;
use App\Models\CategoriaPeso;

test('a category lists only its own weight classes', function () {
    $categoria = Categoria::factory()->create();
    $peso = CategoriaPeso::factory()->for($categoria)->create();
    CategoriaPeso::factory()->create();

    expect($categoria->categoriasPeso->sole()->is($peso))->toBeTrue()
        ->and($peso->categoria->is($categoria))->toBeTrue();
});
