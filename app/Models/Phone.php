<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Phone extends Model {
    use HasFactory;

    protected $fillable = [
        'type',
        'number',
        'ycp_contact_id',
    ];

    public function contact() {
        return $this->belongsTo( YcpContact::class );
    }

    public static function create( string $number, int $contact_id, string $type ): Phone {
        $phone = new Phone();
        //TODO validate and format these
        $phone->number         = $number;
        $phone->type           = $type;
        $phone->ycp_contact_id = $contact_id;
        $phone->save();

        return $phone;
    }
}
