<?php

namespace Database\Factories;

use App\Enums\StatusLuta;
use App\Models\Atleta;
use App\Models\Categoria;
use App\Models\CategoriaPeso;
use App\Models\Evento;
use App\Models\Luta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Luta>
 */
class LutaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'evento_id' => Evento::factory(),
            'categoria_id' => Categoria::factory(),
            // A categoria de peso pertence à mesma modalidade da luta.
            'categoria_peso_id' => fn (array $attributes) => CategoriaPeso::factory()->state([
                'categoria_id' => $attributes['categoria_id'],
            ]),
            'participante_a_id' => Atleta::factory(),
            'participante_b_id' => Atleta::factory(),
            'ordem_na_card' => 1,
            'numero_rounds' => 3,
            'status' => StatusLuta::Agendada,
        ];
    }
}
