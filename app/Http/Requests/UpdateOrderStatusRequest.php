<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('update orders');
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:pending,preparing,ready,completed,cancelled',
        ];
    }
}
