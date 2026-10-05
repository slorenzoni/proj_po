<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\TreinadorRequest;
use App\Models\AtletaEstilo;
use App\Models\Treinador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TreinadorController extends AdminController
{
    public function index(Request $request): Response
    {
        $busca = $this->busca($request);

        return Inertia::render('admin/treinadores/Index', [
            'filtros' => ['busca' => $busca],
            'treinadores' => Treinador::query()
                ->with('user:id,email')
                ->when($busca !== '', fn ($query) => $query->whereLike('nome', "%{$busca}%"))
                ->orderBy('nome')
                ->paginate(self::POR_PAGINA)
                ->withQueryString()
                ->through(fn (Treinador $treinador): array => $this->dados($treinador)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/treinadores/Form', ['treinador' => null]);
    }

    public function store(TreinadorRequest $request): RedirectResponse
    {
        Treinador::query()->create([
            ...$request->safe()->only(['nome', 'pais']),
            'user_id' => $request->userId(),
        ]);

        $this->sucesso('Treinador criado.');

        return to_route('admin.treinadores.index');
    }

    public function edit(Treinador $treinador): Response
    {
        return Inertia::render('admin/treinadores/Form', ['treinador' => $this->dados($treinador)]);
    }

    public function update(TreinadorRequest $request, Treinador $treinador): RedirectResponse
    {
        $treinador->update([
            ...$request->safe()->only(['nome', 'pais']),
            'user_id' => $request->userId(),
        ]);

        $this->sucesso('Treinador atualizado.');

        return to_route('admin.treinadores.index');
    }

    public function destroy(Treinador $treinador): RedirectResponse
    {
        if (AtletaEstilo::query()->where('treinador_id', $treinador->id)->exists()) {
            $this->erro('Este treinador está vinculado a atletas e não pode ser excluído.');

            return back();
        }

        $treinador->delete();

        $this->sucesso('Treinador excluído.');

        return to_route('admin.treinadores.index');
    }

    /**
     * @return array{uuid: string, nome: string, pais: string|null, email_usuario: string|null}
     */
    private function dados(Treinador $treinador): array
    {
        return [
            'uuid' => $treinador->uuid,
            'nome' => $treinador->nome,
            'pais' => $treinador->pais,
            'email_usuario' => $treinador->user?->email,
        ];
    }
}
