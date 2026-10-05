<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusPostagem;
use App\Models\Categoria;
use App\Models\Patrocinador;
use App\Models\Postagem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostagemRequest extends FormRequest
{
    /**
     * O slug vazio é gerado a partir do título; o informado é normalizado.
     */
    protected function prepareForValidation(): void
    {
        $origem = $this->filled('slug') ? $this->input('slug') : $this->input('titulo');

        $this->merge(['slug' => Str::slug(is_string($origem) ? $origem : '')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:220',
                Rule::unique(Postagem::class, 'slug')->withoutTrashed()->ignore($this->route('postagem')),
            ],
            'conteudo' => ['required', 'string', 'max:60000'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'capa' => ['nullable', 'image', 'max:4096'],
            'patrocinador_id' => ['nullable', 'integer', Rule::exists(Patrocinador::class, 'id')->withoutTrashed()],
            'fonte_original_url' => ['nullable', 'url', 'max:255'],
            'categoria_id' => ['nullable', 'integer', Rule::exists(Categoria::class, 'id')->withoutTrashed()],
            'status' => ['required', Rule::enum(StatusPostagem::class)],
            'data_publicacao' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'Já existe uma postagem com este endereço (slug).',
        ];
    }

    /**
     * Campos do model. Publicar sem data usa a data de hoje.
     *
     * @return array<string, mixed>
     */
    public function atributos(): array
    {
        $dados = $this->safe()->except('capa');

        if ($dados['status'] === StatusPostagem::Publicado->value && empty($dados['data_publicacao'])) {
            $dados['data_publicacao'] = today()->toDateString();
        }

        return $dados;
    }
}
