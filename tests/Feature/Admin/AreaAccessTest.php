<?php

use App\Enums\NivelAcesso;

/*
| Matriz de permissões do painel (decidida em 04/10/2026):
| cadastrador → cadastros; moderador → verificações; super-admin → tudo.
*/
test('each access level reaches only its own admin areas', function (NivelAcesso $nivel, string $rota, bool $permitido) {
    $response = $this->actingAs(administrador($nivel))->get(route($rota));

    $permitido ? $response->assertOk() : $response->assertForbidden();
})->with([
    'cadastrador vê cadastros' => [NivelAcesso::Cadastrador, 'admin.organizacoes.index', true],
    'cadastrador vê eventos' => [NivelAcesso::Cadastrador, 'admin.eventos.index', true],
    'cadastrador vê patrocínio' => [NivelAcesso::Cadastrador, 'admin.banners.index', true],
    'cadastrador não vê verificações' => [NivelAcesso::Cadastrador, 'admin.verificacoes.index', false],
    'cadastrador não vê usuários' => [NivelAcesso::Cadastrador, 'admin.usuarios.index', false],
    'cadastrador não vê configuração' => [NivelAcesso::Cadastrador, 'admin.configuracoes.pontuacao.edit', false],
    'moderador vê verificações' => [NivelAcesso::Moderador, 'admin.verificacoes.index', true],
    'moderador não vê cadastros' => [NivelAcesso::Moderador, 'admin.organizacoes.index', false],
    'moderador não vê usuários' => [NivelAcesso::Moderador, 'admin.usuarios.index', false],
    'moderador não vê configuração' => [NivelAcesso::Moderador, 'admin.configuracoes.pontuacao.edit', false],
    'super-admin vê cadastros' => [NivelAcesso::SuperAdmin, 'admin.atletas.index', true],
    'super-admin vê verificações' => [NivelAcesso::SuperAdmin, 'admin.verificacoes.index', true],
    'super-admin vê usuários' => [NivelAcesso::SuperAdmin, 'admin.usuarios.index', true],
    'super-admin vê configuração' => [NivelAcesso::SuperAdmin, 'admin.configuracoes.pontuacao.edit', true],
]);

test('an access level cannot write outside its areas', function () {
    $this->actingAs(administrador(NivelAcesso::Moderador))
        ->post(route('admin.organizacoes.store'), ['nome' => 'UFC'])
        ->assertForbidden();

    $this->assertDatabaseCount('organizacoes', 0);
});

test('the menu receives only the areas of the access level', function (NivelAcesso $nivel, array $areas) {
    $this->actingAs(administrador($nivel))
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.areasAdmin', $areas));
})->with([
    'cadastrador' => [NivelAcesso::Cadastrador, ['cadastros']],
    'moderador' => [NivelAcesso::Moderador, ['verificacoes']],
    'super-admin' => [NivelAcesso::SuperAdmin, ['cadastros', 'verificacoes', 'usuarios', 'configuracoes']],
]);
