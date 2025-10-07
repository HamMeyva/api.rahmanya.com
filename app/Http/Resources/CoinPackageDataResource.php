<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoinPackageDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'coin_amount' => $this->coin_amount,
            'price' => $this->price,
            'is_discount' => $this->is_discount,
            'discounted_price' => $this->discounted_price,
            'draw_discounted_price' => $this->draw_discounted_price,
            'get_final_price' => $this->get_final_price,
            'draw_final_price' => $this->draw_final_price,
            'get_price' => $this->get_price,
            'draw_price' => $this->draw_price,
            'get_discount_amount' => $this->get_discount_amount,
            'draw_discount_amount' => $this->draw_discount_amount,
            'country' => [
                'id' => $this?->country?->id ?? null,
                'name' => $this?->country?->name ?? null,
            ],
            'currency' => [
                'id' => $this?->currency?->id ?? null,
                'code' => $this?->currency?->code ?? null,
                'symbol' => $this?->currency?->symbol ?? null,
                'name' => $this?->currency?->name ?? null,
            ],
        ];
    }
}
