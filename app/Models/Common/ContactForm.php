<?php

namespace App\Models\Common;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperContactForm
 */
class ContactForm extends Model
{
    public $table = 'contact_forms';

    protected $fillable = [
        'is_read',
        'editor_id',
        'full_name',
        'message',
        'user_id',
        'phone',
        'email',
        'read_at',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')
            ->withDefault(
                [
                    'id' => 0,
                    'name' => 'Hesap bulunamadı',
                ]);
    }
}
