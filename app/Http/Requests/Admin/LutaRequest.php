<?php

namespace App\Http\Requests\Admin;

use App\Enums\TipoCard;
use App\Models\Atleta;
use App\Models\Categoria;
use App\Models\CategoriaPeso;
use App\Models\Evento;
use App\Models\Luta;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LutaRequest extends FormRequest
{
    /**
     * Durações de luta aceitas em modalidades com rounds.
     */
    private const NUMEROS_DE_ROUNDS = [3, 5];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $atleta = Rule::exists(Atleta::class, 'id')->withoutTrashed();

        return [
            'categoria_id' => ['required', 'integer', Rule::exists(Categoria::class, 'id')->withoutTrashed()],
            'categoria_peso_id' => [
                'required',
                'integer',
                // A categoria de peso precisa ser da modalidade escolhida.
                Rule::exists(CategoriaPeso::class, 'id')
                    ->where('categoria_id', $this->integer('categoria_id'))
                    ->withoutTrashed(),
            ],
            'participante_a_id' => ['required', 'integer', $atleta],
            'participante_b_id' => ['required', 'integer', 'different:participante_a_id', $atleta],
            'ordem_na_card' => [
                'required',
                'integer',
                'min:1',
                Rule::unique(Luta::class, 'ordem_na_card')
                    ->where('evento_id', $this->eventoId())
                    ->withoutTrashed()
                    ->ignore($this->route('luta')),
            ],
            'tipo_card' => ['nullable', Rule::enum(TipoCard::class)],
            'numero_rounds' => $this->usaRounds()
                ? ['required', 'integer', Rule::in(self::NUMEROS_DE_ROUNDS)]
                : ['nullable'],
            'chance_do_a' => ['nullable', 'numeric', 'between:0,100'],
            'chance_do_b' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'categoria_peso_id.exists' => 'A categoria de peso não pertence à modalidade escolhida.',
            'participante_b_id.different' => 'Os dois participantes precisam ser atletas diferentes.',
            'ordem_na_card.unique' => 'Já existe uma luta nessa posição do card.',
        ];
    }

    /**
     * Campos validados; modalidades sem rounds (ex.: Judô) gravam "numero_rounds" nulo.
     *
     * @return array<string, mixed>
     */
    public function atributos(): array
    {
        return [
            ...$this->validated(),
            'numero_rounds' => $this->usaRounds() ? $this->integer('numero_rounds') : null,
        ];
    }

    private function usaRounds(): bool
    {
        return (bool) Categoria::query()->whereKey($this->integer('categoria_id'))->value('usa_rounds');
    }

    /**
     * Na criação o evento vem da rota; na edição, da própria luta.
     */
    private function eventoId(): int
    {
        $evento = $this->route('evento');
        $luta = $this->route('luta');

        return $evento instanceof Evento
            ? $evento->id
            : ($luta instanceof Luta ? $luta->evento_id : 0);
    }
}
