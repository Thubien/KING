<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    /**
     * The "booted" method of the model.
     */
    protected static function bootBelongsToCompany()
    {
        // Otomatik olarak company_id ekle
        static::creating(function ($model) {
            if (!$model->company_id && auth()->check()) {
                $model->company_id = auth()->user()->company_id;
            }
        });

        // Global scope ekle - sadece kendi company verilerini görsün
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('company_id', auth()->user()->company_id);
            }
        });
    }

    /**
     * Company scope'u devre dışı bırak (admin için)
     */
    public function scopeWithoutCompany(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }

    /**
     * Belirli bir company için filtrele
     */
    public function scopeForCompany(Builder $query, $companyId = null): Builder
    {
        $companyId = $companyId ?? auth()->user()->company_id;
        return $query->where('company_id', $companyId);
    }
}