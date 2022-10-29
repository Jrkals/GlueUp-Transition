<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model {
    use HasFactory;

    public function contacts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YcpContact::class )->withPivot( 'active' );
    }

    public static function getOrCreateFromName( string $name ) {
        $existing = Plan::query()->where( 'name', '=', $name )->get();
        if ( $existing->isEmpty() ) {
            $plan       = new Plan();
            $plan->name = $name;
            $plan->save();

            return $plan;
        }

        return $existing->first();
    }

    public function differentPlan( Plan $p ): bool {
        return $p->name === $this->name;
    }
}
