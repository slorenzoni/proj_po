<?php

namespace App\Http\Requests\Site;

use App\Enums\MetodoPalpite;
use App\Models\Luta;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PalpiteRequest extends FormRequest
{
    /**
     * Vencedor obrigatório; método e round opcionais. O round não se aplica a modalidades
     * sem rounds nem ao método "Decisão", que sempre acontece no último round.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $luta = $this->luta();
        $usaRounds = $luta->numero_rounds !== null;

        return [
            'vencedor_id' => ['required', 'integer', Rule::in([$luta->participante_a_id, $luta->participante_b_id])],
            'metodo' => ['nullable', Rule::enum(MetodoPalpite::class)->only(MetodoPalpite::paraModalidade($usaRounds))],
            'round' => $usaRounds && $this->metodo() !== MetodoPalpite::Decisao
                ? ['nullable', 'integer', 'between:1,'.$luta->numero_rounds]
                : ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vencedor_id.required' => 'Escolha quem vence a luta.',
            'vencedor_id.in' => 'O vencedor precisa ser um dos dois participantes da luta.',
            'round.prohibited' => 'Este palpite não permite escolher o round.',
        ];
    }

    public function metodo(): ?MetodoPalpite
    {
        return MetodoPalpite::tryFrom((string) $this->input('metodo'));
    }

    private function luta(): Luta
    {
        $luta = $this->route('luta');

        abort_unless($luta instanceof Luta, 404);

        return $luta;
    }
}
