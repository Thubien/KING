<?php

namespace App\Traits;

trait HasSimpleAuthorization
{
    /**
     * Check if user can access this resource
     */
    public static function canAccessResource(): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // Super admin can access everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Define resource permissions
        $resourcePermissions = static::getResourcePermissions();
        
        foreach ($resourcePermissions as $role => $allowed) {
            if ($user->hasRole($role) && $allowed) {
                return true;
            }
        }

        return false;
    }

    /**
     * Define which roles can access this resource
     * Override in each resource
     */
    protected static function getResourcePermissions(): array
    {
        return [
            'owner' => true,
            'partner' => false,
            'staff' => false,
        ];
    }

    /**
     * Apply company scoping to query
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        // Super admin sees everything
        if ($user?->isSuperAdmin()) {
            return $query;
        }

        // Apply company scoping (already handled by global scopes)
        return $query;
    }

    /**
     * Check if user can view this specific record
     */
    public static function canView($record): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // Super admin can view everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company isolation check
        if (method_exists($record, 'belongsToCompany') && !$record->belongsToCompany($user->company_id)) {
            return false;
        }

        return static::canAccessResource();
    }

    /**
     * Check if user can create records
     */
    public static function canCreate(): bool
    {
        return static::canAccessResource();
    }

    /**
     * Check if user can edit this record
     */
    public static function canEdit($record): bool
    {
        return static::canView($record);
    }

    /**
     * Check if user can delete this record
     */
    public static function canDelete($record): bool
    {
        $user = auth()->user();
        
        // Only owners and super admins can delete
        return $user?->isSuperAdmin() || $user?->isOwner();
    }
}