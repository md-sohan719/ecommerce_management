<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ShopOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $owner = $this->route('owner');
        $creating = $owner === null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($owner?->id),
            ],
            // Required when creating; optional when updating (blank = keep current password)
            'password' => [$creating ? 'required' : 'nullable', 'string', Password::min(8)],
        ];
    }
}
