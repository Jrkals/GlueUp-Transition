<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YcpEvent extends Model {
    use HasFactory;

    public function contacts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YcpContact::class, 'ycp_events_contacts' )->withPivot( 'attended' );
    }

    public function chapter(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo( Chapter::class );
    }
}
