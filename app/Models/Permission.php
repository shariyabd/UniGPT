<?php

// app/Models/Permission.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->withTimestamps();
    }

    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', strtolower($slug))->first();
    }

    public static function findByEnum(\App\Enums\Permission $permission): ?self
    {
        return self::where('slug', $permission->getSlug())->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function getEnum(): ?\App\Enums\Permission
    {
        foreach (\App\Enums\Permission::cases() as $permissionEnum) {
            if ($permissionEnum->getSlug() === $this->slug) {
                return $permissionEnum;
            }
        }

        return null;
    }

    public static function createFromEnum(\App\Enums\Permission $permissionEnum): self
    {
        return self::create([
            'name' => $permissionEnum->getLabel(),
            'slug' => $permissionEnum->getSlug(),
            'description' => "Permission to {$permissionEnum->getLabel()}",
            'category' => $permissionEnum->getCategory(),
            'is_active' => true,
        ]);
    }
}
