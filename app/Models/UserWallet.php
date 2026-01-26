<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWallet extends Model
{
    protected $fillable = [
        'user_id',
        'coin_balance',
        'total_earned',
        'total_spent'
    ];

    protected $casts = [
        'coin_balance' => 'integer',
        'total_earned' => 'integer',
        'total_spent' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasEnoughCoins(int $amount): bool
    {
        return $this->coin_balance >= $amount;
    }

    public function deductCoins(int $amount): void
    {
        $this->coin_balance -= $amount;
        $this->total_spent += $amount;
        $this->save();
    }

    public function addCoins(int $amount): void
    {
        $this->coin_balance += $amount;
        $this->total_earned += $amount;
        $this->save();
    }
}
