<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\ConfiguracaoPontuacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConfiguracaoPontuacao>
 */
class ConfiguracaoPontuacaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'categoria_id' => null,
            'pontos_vencedor' => 10,
            'pontos_vencedor_metodo' => 15,
            'pontos_vencedor_round' => 15,
            'pontos_perfeito' => 22,
            'prazo_placar_fans_minutos' => 5,
        ];
    }

    public function paraCategoria(Categoria $categoria): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria_id' => $categoria->getKey(),
        ]);
    }
}
