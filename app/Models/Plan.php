<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model {
    use HasFactory;

    public function contacts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YcpContact::class )->withPivot( 'active', 'start_date', 'expiry_date' );
    }

    public static function getOrCreatePlan( array $data ) {
        $existing = Plan::query()->where( 'name', '=', $data['name'] )->get();
        if ( $existing->isNotEmpty() ) {
            return $existing->first();
        }
        $plan       = new Plan();
        $plan->name = $data['name'];
        $plan->save();

        return $plan;
    }

    public function differentPlan( Plan $p ): bool {
        return $p->name === $this->name;
    }
}
