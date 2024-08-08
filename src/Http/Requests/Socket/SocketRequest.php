<?php

namespace FimPablo\SigExtenders\Http\Requests\Socket;

use FimPablo\SigExtenders\Http\Requests\NoRedirects;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SocketRequest extends FormRequest
{
    use NoRedirects;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'model' => ['required', 'string'],
            'wheres' => ['required', 'array'],
            'relations' => ['nullable', 'array'],
            'relations.*' => ['required', 'string']
        ];
    }

    public function beforeValidate(): void
    {
        $this->ids = collect($this->ids)->unique()->all();
    }
}
