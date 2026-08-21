<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class IndexChatMessagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nickname' => ['nullable', 'string', 'max:100'],
            'room' => ['nullable', 'string', 'regex:/^(home|public:\d+)$/'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array{nickname: string, room: string} */
    public function filters(): array
    {
        return [
            'nickname' => (string) $this->validated('nickname', ''),
            'room' => (string) $this->validated('room', ''),
        ];
    }
}
