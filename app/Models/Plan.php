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
        $clean_name  = str_replace( '/', '_', $data['name'] );
        $mapped_name = self::mapPlanNames( $clean_name );
        $existing    = Plan::query()->where( 'name', '=', $mapped_name )->get();
        if ( $existing->isNotEmpty() ) {
            return $existing->first();
        }
        $plan       = new Plan();
        $plan->name = $mapped_name;
        $plan->save();

        return $plan;
    }

    public function differentPlan( ?Plan $p ): bool {
        if ( ! $p ) {
            return true;
        }

        return $p->name !== $this->name;
    }

    private static function mapPlanNames( string $name ): string {
        return match ( $name ) {
            'Admin' => 'Chapter Leader',
            'Chapter Leader', 'Belong', 'Belong Plus', 'Executive Mentor Membership',
            'Chapter Chaplain', 'Chapter Board Member' => $name,
            default => 'Legacy'
        };
    }
}
