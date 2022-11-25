<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailValidation extends Model {
    use HasFactory;

    public $primaryKey = 'email';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'valid' => 'boolean'
    ];

    protected $fillable = [
        'email',
        'valid'
    ];

    public static function emailIsValid( string $email ): bool {
        $validation = EmailValidation::query()->find( $email );
        if ( ! $email || $validation->valid ) {
            return true;
        }

        return false;
    }
}
