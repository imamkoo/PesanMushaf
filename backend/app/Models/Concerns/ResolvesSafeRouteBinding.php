<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Binding route yang aman untuk Postgres: nilai non-angka tidak pernah
 * dibandingkan dengan kolom id integer (sebelumnya memicu 500).
 * Model dengan $routeSlugColumn juga bisa dicari lewat slug.
 */
trait ResolvesSafeRouteBinding
{
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        $value = (string) $value;

        if (preg_match('/^[1-9]\d{0,17}$/', $value) === 1) {
            return $this->newQuery()->whereKey((int) $value)->first();
        }

        $slugColumn = property_exists($this, 'routeSlugColumn') ? $this->routeSlugColumn : null;

        if ($slugColumn === null || $value === '' || mb_strlen($value) > 255) {
            return null;
        }

        return $this->newQuery()->where($slugColumn, $value)->first();
    }
}
