<?php

namespace App\Http\Requests\Admin;

use App\Enums\MetodoVitoria;
use App\Models\Luta;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EncerrarLutaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $luta = $this->luta();
        $usaRounds = $luta->numero_rounds !== null;

        return [
            // Só os métodos da modalidade da luta (com ou sem rounds).
            'metodo_vitoria' => ['required', Rule::enum(MetodoVitoria::class)->only(MetodoVitoria::paraModalidade($usaRounds))],
            'vencedor_id' => [
                Rule::requiredIf(fn (): bool => $this->metodo()?->temVencedor() ?? false),
                'nullable',
                'integer',
                Rule::in([$luta->participante_a_id, $luta->participante_b_id]),
            ],
            'round_fim' => $usaRounds
                ? ['nullable', 'integer', 'between:1,'.$luta->numero_rounds]
                : ['prohibited'],
            'tempo_fim' => ['nullable', 'string', 'regex:/^\d{1,2}:[0-5]\d$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vencedor_id.required' => 'Informe o vencedor.',
            'vencedor_id.in' => 'O vencedor precisa ser um dos dois participantes da luta.',
            'tempo_fim.regex' => 'Informe o tempo no formato mm:ss.',
        ];
    }

    public function metodo(): ?MetodoVitoria
    {
        return MetodoVitoria::tryFrom((string) $this->input('metodo_vitoria'));
    }

    private function luta(): Luta
    {
        $luta = $this->route('luta');

        abort_unless($luta instanceof Luta, 404);

        return $luta;
    }
}
