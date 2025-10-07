<?php

namespace App\View\Components\Admin\FormElements;

use Closure;
use App\Models\Coupon;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class CouponDiscountTypeSelect extends Component
{
    public $options;

    public function __construct()
    {
        foreach(Coupon::$discountTypes as $key => $label) {
            $this->options[] = [
                'value' => $key,
                'label' => $label
            ];
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.coupon-discount-type-select');
    }
}
