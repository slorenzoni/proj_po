<?php

namespace Database\Factories;

use App\Enums\ModeloCobranca;
use App\Enums\PosicaoBanner;
use App\Enums\StatusBanner;
use App\Models\Banner;
use App\Models\Patrocinador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patrocinador_id' => Patrocinador::factory(),
            'imagem_url' => 'banners/'.fake()->uuid().'.jpg',
            'posicao' => PosicaoBanner::Home,
            'data_inicio' => today(),
            'status' => StatusBanner::Ativo,
            'modelo_cobranca' => ModeloCobranca::Fixo,
        ];
    }
}
