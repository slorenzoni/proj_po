<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\AreaAdmin;
use App\Enums\PapelPadrao;
use App\Enums\PlanoAssinatura;
use App\Enums\StatusAssinatura;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Identidade de autenticação única. Os papéis NÃO são exclusivos: um usuário pode ter
 * perfil_cliente e perfil_administrador ao mesmo tempo, além de papéis "só-permissão".
 *
 * @property int $id
 * @property string $uuid
 * @property PapelPadrao|null $papel_padrao
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property bool $verificado
 * @property Carbon|null $verificado_em
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read PerfilCliente|null $perfilCliente
 * @property-read PerfilAdministrador|null $perfilAdministrador
 */
#[Fillable(['name', 'email', 'password', 'papel_padrao'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasPublicUuid, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * @return HasOne<PerfilCliente, $this>
     */
    public function perfilCliente(): HasOne
    {
        return $this->hasOne(PerfilCliente::class);
    }

    /**
     * @return HasOne<PerfilAdministrador, $this>
     */
    public function perfilAdministrador(): HasOne
    {
        return $this->hasOne(PerfilAdministrador::class);
    }

    /**
     * @return HasMany<Assinatura, $this>
     */
    public function assinaturas(): HasMany
    {
        return $this->hasMany(Assinatura::class);
    }

    /**
     * @return HasMany<Palpite, $this>
     */
    public function palpites(): HasMany
    {
        return $this->hasMany(Palpite::class);
    }

    /**
     * Cadastro de treinador ligado a esta conta, se houver.
     *
     * @return HasOne<Treinador, $this>
     */
    public function treinador(): HasOne
    {
        return $this->hasOne(Treinador::class);
    }

    /**
     * Cadastro de atleta ligado a esta conta, se houver.
     *
     * @return HasOne<Atleta, $this>
     */
    public function atleta(): HasOne
    {
        return $this->hasOne(Atleta::class);
    }

    /**
     * Papéis "só-permissão" ativos (pivots com soft delete são ignorados).
     *
     * @return BelongsToMany<Papel, $this, UserPapel>
     */
    public function papeis(): BelongsToMany
    {
        return $this->belongsToMany(Papel::class, 'papel_user')
            ->using(UserPapel::class)
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /**
     * O papel é definido pela existência do perfil, não por um campo exclusivo.
     * Usa a relação carregada (uma consulta por requisição, no máximo).
     */
    public function isAdministrador(): bool
    {
        return $this->perfilAdministrador !== null;
    }

    /**
     * O nível do perfil de administrador define quais áreas do painel o usuário acessa.
     */
    public function podeAcessarArea(AreaAdmin $area): bool
    {
        return in_array($area, $this->perfilAdministrador?->nivel_acesso->areas() ?? [], true);
    }

    /**
     * Plano da assinatura ativa; quem não tem assinatura ativa é tratado como Free.
     */
    public function plano(): PlanoAssinatura
    {
        $assinatura = $this->assinaturas()
            ->where('status', StatusAssinatura::Ativa)
            ->latest('id')
            ->first();

        return $assinatura->plano ?? PlanoAssinatura::Free;
    }

    public function isMembro(): bool
    {
        return $this->plano() === PlanoAssinatura::Membro;
    }

    /**
     * Troca o plano: cancela a assinatura ativa e abre outra. Usado pelo painel enquanto
     * não há gateway de pagamento (a assinatura criada não gera cobrança).
     */
    public function trocarPlano(PlanoAssinatura $plano): void
    {
        DB::transaction(function () use ($plano): void {
            $this->assinaturas()
                ->where('status', StatusAssinatura::Ativa)
                ->get()
                ->each(fn (Assinatura $assinatura) => $assinatura->update(['status' => StatusAssinatura::Cancelada]));

            $this->assinaturas()->create([
                'plano' => $plano,
                'status' => StatusAssinatura::Ativa,
                'valor' => 0,
                'data_inicio' => now()->toDateString(),
            ]);
        });
    }

    /**
     * Destaque exibido ao lado dos comentários de quem tem um papel especial (decisão de
     * 01/10/2026). Nulo para o cliente comum.
     */
    public function destaqueNosComentarios(): ?string
    {
        return match (true) {
            $this->isAdministrador() => 'Administrador',
            $this->hasPapel(Papel::COMENTARISTA) => Papel::COMENTARISTA,
            $this->atleta !== null => 'Atleta',
            $this->treinador !== null => 'Treinador',
            default => null,
        };
    }

    /**
     * Comentam: Membro ativo e os papéis especiais (comentarista, atleta ou treinador com
     * conta, administrador).
     */
    public function podeComentar(): bool
    {
        return $this->destaqueNosComentarios() !== null || $this->isMembro();
    }

    /**
     * Decisão de 06/10/2026: dão dicas os comentaristas e os Membros com selo de verificado.
     * O selo só é concedido a Membros; se a assinatura acabar, perde-se o direito.
     */
    public function podeDarDica(): bool
    {
        return $this->hasPapel(Papel::COMENTARISTA) || ($this->verificado && $this->isMembro());
    }

    public function isCliente(): bool
    {
        return $this->perfilCliente !== null;
    }

    public function hasPapel(string $nome): bool
    {
        return $this->papeis->contains('nome', $nome);
    }

    /**
     * Atribui o papel se ainda não estiver ativo. O attach com pivot customizado
     * dispara os eventos do UserPapel (UUID e auditoria).
     */
    public function atribuirPapel(Papel $papel): void
    {
        if ($this->papeis()->whereKey($papel->getKey())->exists()) {
            return;
        }

        $this->papeis()->attach($papel->getKey());
        $this->unsetRelation('papeis');
    }

    /**
     * Remove o papel com soft delete (o detach() padrão apagaria fisicamente).
     */
    public function removerPapel(Papel $papel): void
    {
        UserPapel::query()
            ->where('user_id', $this->getKey())
            ->where('papel_id', $papel->getKey())
            ->get()
            ->each(fn (UserPapel $vinculo) => $vinculo->delete());

        $this->unsetRelation('papeis');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'papel_padrao' => PapelPadrao::class,
            'verificado' => 'boolean',
            'verificado_em' => 'datetime',
        ];
    }
}
