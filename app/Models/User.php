<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\PapelPadrao;
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
