<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function permissionSlugs(): array
    {
        $this->loadMissing('role.permissions');
        $role = $this->role;

        if (! $role) {
            return [];
        }

        $slugs = $role->slug === 'admin'
            ? Permission::query()->orderBy('id')->pluck('slug')->all()
            : $role->permissions->pluck('slug')->all();

        foreach ($slugs as $slug) {
            if (str_ends_with($slug, '.manage')) {
                $slugs[] = substr($slug, 0, -7).'.view';
            }
        }

        return array_values(array_unique($slugs));
    }

    public function allows(string $permission): bool
    {
        return in_array($permission, $this->permissionSlugs(), true);
    }

    public function accessPayload(): array
    {
        $this->loadMissing('role');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'is_active' => $this->is_active,
            'role' => $this->role ? [
                'id' => $this->role->id,
                'slug' => $this->role->slug,
                'name' => $this->role->name,
            ] : null,
            'permissions' => $this->permissionSlugs(),
        ];
    }
}
