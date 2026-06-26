<?php

namespace App\Http\Requests;

use App\Models\Send;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateSendRequest extends SendRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    //    public function authorize(): bool
    //    {
    //        return Gate::allows('update', $this->route('send'));
    //    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['name'] = [
            'required',
            'string',
            'max:255',
            Rule::unique(Send::class)->where('user_id', auth()->id())->ignore($this->route('send')),
        ];

        return $rules;
    }
}
