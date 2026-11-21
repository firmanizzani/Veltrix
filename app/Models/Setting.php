<?php

namespace App\Models;

class Setting extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public static function business(): array
    {
        return array_replace(
            [
                'business_name' => 'Veltrix Coffee',
                'contact_email' => '',
                'receipt_footer' =>
                    'Terima kasih sudah singgah. Sampai di cangkir berikutnya.',
            ],
            static::pluck('value', 'key')->all(),
        );
    }
}
