<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NivelAcesso;
use App\Enums\PlanoAssinatura;
use App\Models\Papel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Usuários: consulta, papéis "só-permissão" e perfil de administrador.
 *
 * Ninguém altera o próprio perfil de administrador por aqui — evita que o último
 * super-admin se rebaixe ou se remova por engano.
 */
class UsuarioController extends AdminController
{
    public function index(Request $request): Response
    {
        $busca = $this->busca($request);

        return Inertia::render('admin/usuarios/Index', [
            'filtros' => ['busca' => $busca],
            'usuarios' => User::query()
                ->with(['perfilAdministrador', 'perfilCliente'])
                ->when($busca !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->whereLike('name', "%{$busca}%")
                    ->orWhereLike('email', "%{$busca}%")))
                ->orderBy('name')
                ->paginate(self::POR_PAGINA)
                ->withQueryString()
                ->through(fn (User $usuario): array => [
                    'uuid' => $usuario->uuid,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'cliente' => $usuario->isCliente(),
                    'nivel_acesso' => $usuario->perfilAdministrador?->nivel_acesso->label(),
                ]),
        ]);
    }

    public function show(Request $request, User $usuario): Response
    {
        $usuario->load(['perfilAdministrador', 'perfilCliente', 'papeis']);

        return Inertia::render('admin/usuarios/Show', [
            'usuario' => [
                'uuid' => $usuario->uuid,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'email_verificado' => $usuario->email_verified_at !== null,
                'cliente' => $usuario->isCliente(),
                'nivel_acesso' => $usuario->perfilAdministrador?->nivel_acesso->value,
                'plano' => $usuario->plano()->value,
                'criado_em' => $usuario->created_at?->toIso8601String(),
                'papeis' => $usuario->papeis->map(fn (Papel $papel): array => [
                    'uuid' => $papel->uuid,
                    'nome' => $papel->nome,
                ]),
            ],
            'ehProprioUsuario' => $usuario->is($request->user()),
            'papeisDisponiveis' => Papel::query()
                ->whereNotIn('id', $usuario->papeis->modelKeys())
                ->orderBy('nome')
                ->get(['id', 'nome'])
                ->map(fn (Papel $papel): array => ['value' => $papel->id, 'label' => $papel->nome]),
            'niveis' => NivelAcesso::opcoes(),
            'planos' => PlanoAssinatura::opcoes(),
        ]);
    }

    public function atribuirPapel(Request $request, User $usuario): RedirectResponse
    {
        $dados = $request->validate([
            'papel_id' => ['required', 'integer', Rule::exists(Papel::class, 'id')->withoutTrashed()],
        ]);

        $usuario->atribuirPapel(Papel::query()->whereKey($dados['papel_id'])->firstOrFail());

        $this->sucesso('Papel atribuído.');

        return back();
    }

    public function removerPapel(User $usuario, Papel $papel): RedirectResponse
    {
        $usuario->removerPapel($papel);

        $this->sucesso('Papel removido.');

        return back();
    }

    /**
     * Define o plano do cliente pelo painel. Existe porque ainda não há gateway de
     * pagamento: a assinatura criada aqui não gera cobrança.
     */
    public function definirPlano(Request $request, User $usuario): RedirectResponse
    {
        $dados = $request->validate(['plano' => ['required', Rule::enum(PlanoAssinatura::class)]]);

        if (! $usuario->isCliente()) {
            $this->erro('Só contas de cliente têm plano.');

            return back();
        }

        $plano = PlanoAssinatura::from($dados['plano']);

        if ($usuario->plano() !== $plano) {
            $usuario->trocarPlano($plano);
        }

        $this->sucesso("Plano definido como {$plano->label()}.");

        return back();
    }

    /**
     * Concede o perfil de administrador ou altera o nível de quem já tem.
     */
    public function definirAdministrador(Request $request, User $usuario): RedirectResponse
    {
        if ($this->ehProprioPerfil($request, $usuario)) {
            return back();
        }

        $dados = $request->validate(['nivel_acesso' => ['required', Rule::enum(NivelAcesso::class)]]);

        $usuario->perfilAdministrador()->updateOrCreate([], ['nivel_acesso' => $dados['nivel_acesso']]);

        $this->sucesso('Perfil de administrador atualizado.');

        return back();
    }

    public function revogarAdministrador(Request $request, User $usuario): RedirectResponse
    {
        if ($this->ehProprioPerfil($request, $usuario)) {
            return back();
        }

        $usuario->perfilAdministrador?->delete();

        $this->sucesso('Perfil de administrador revogado.');

        return back();
    }

    private function ehProprioPerfil(Request $request, User $usuario): bool
    {
        if (! $usuario->is($request->user())) {
            return false;
        }

        $this->erro('Você não pode alterar o seu próprio perfil de administrador.');

        return true;
    }
}
