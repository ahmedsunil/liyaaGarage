<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Activitylog\LogOptions;
use App\Support\Enums\UserStatuses;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\CausesActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasMedia
{
    use CausesActivity;
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use InteractsWithMedia;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $attributes = [
        'status' => UserStatuses::Pending,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'contact_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => UserStatuses::class,
    ];

    public function avatarImages(): MorphMany
    {
        return $this->media()->where('collection_name', 'avatar');
    }

    public function scopeUserVisible($query, ?User $user = null)
    {
        $user ??= auth()->user();
        if ($user->can('view any user')) {
            return $query;
        }

        if ($user->can('view users')) {
            return $query->where('id', $user->id);
        }

        return $query->where('id', -1);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasVerifiedEmail();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar;
    }

    public function avatar(): Attribute
    {
        return Attribute::get(function () {
            $this->loadMissing('avatarImages');

            return $this->avatarImages->first()?->getUrl() ?: asset('images/anonymous-user.png');
        });
    }

    public function formattedName(): Attribute
    {
        return Attribute::get(function () {
            return $this->name;
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logExcept($this->hidden)
            ->logAll();
    }
}
