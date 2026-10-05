<?php

namespace App\Http\Controllers\Admin;

use App\Models\Atleta;
use App\Models\AtletaFoto;
use App\Services\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fotos do atleta, gerenciadas dentro da tela do atleta. Cada atleta tem no máximo
 * AtletaFoto::LIMITE_POR_ATLETA fotos ativas e exatamente uma principal (se tiver alguma).
 */
class AtletaFotoController extends AdminController
{
    private const PASTA = 'atletas';

    public function __construct(private readonly MediaStorage $media) {}

    public function store(Request $request, Atleta $atleta): RedirectResponse
    {
        $request->validate(['foto' => ['required', 'image', 'max:4096']]);

        $posicoesOcupadas = $atleta->fotos()->pluck('ordem')->all();
        $posicoesLivres = array_diff(range(1, AtletaFoto::LIMITE_POR_ATLETA), $posicoesOcupadas);

        if ($posicoesLivres === []) {
            throw ValidationException::withMessages([
                'foto' => 'O atleta já tem '.AtletaFoto::LIMITE_POR_ATLETA.' fotos. Exclua uma antes de enviar outra.',
            ]);
        }

        $atleta->fotos()->create([
            'foto_url' => $this->media->store($request->file('foto'), self::PASTA),
            'ordem' => min($posicoesLivres),
            // A primeira foto enviada vira a principal.
            'principal' => $posicoesOcupadas === [],
        ]);

        $this->sucesso('Foto adicionada.');

        return back();
    }

    /**
     * Define a foto como principal, desmarcando as demais do mesmo atleta.
     */
    public function update(AtletaFoto $foto): RedirectResponse
    {
        DB::transaction(function () use ($foto): void {
            AtletaFoto::query()
                ->where('atleta_id', $foto->atleta_id)
                ->whereKeyNot($foto->id)
                ->where('principal', true)
                ->get()
                ->each(fn (AtletaFoto $outra) => $outra->update(['principal' => false]));

            $foto->update(['principal' => true]);
        });

        $this->sucesso('Foto principal definida.');

        return back();
    }

    public function destroy(AtletaFoto $foto): RedirectResponse
    {
        DB::transaction(function () use ($foto): void {
            $foto->delete();

            // Se a principal foi excluída, a próxima na ordem assume.
            if ($foto->principal) {
                AtletaFoto::query()
                    ->where('atleta_id', $foto->atleta_id)
                    ->orderBy('ordem')
                    ->first()
                    ?->update(['principal' => true]);
            }
        });

        $this->sucesso('Foto excluída.');

        return back();
    }
}
