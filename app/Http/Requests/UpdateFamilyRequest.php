<?php

namespace App\Http\Requests;

use App\Rules\UniqueMemberEmail;
use App\Support\MemberTextParser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'user' => ['required', 'string'],
            'number_bank' => ['required', 'string'],
            'name_bank' => ['required', 'string'],
            'payment_at' => ['nullable', 'date'],
            'number_phone' => ['nullable', 'string'],
            'afiilicate_by' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'monthly_payment' => ['nullable', 'integer'],
            'auto_payment_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'member_texts' => ['array'],
            'member_texts.*' => ['nullable', 'string'],
            'member_ids' => ['array'],
            'member_ids.*' => ['nullable', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $excludeFamilyId = (int) $this->route('family')->id;

        $validator->after(function (Validator $validator) use ($excludeFamilyId) {
            $seen = [];
            foreach ((array) $this->input('member_texts', []) as $index => $text) {
                $email = MemberTextParser::parse($text)['email'];
                if ($email === null) {
                    continue;
                }
                if (isset($seen[$email])) {
                    $validator->errors()->add("member_texts.$index", "Email {$email} bị trùng giữa các thành viên.");
                    continue;
                }
                $seen[$email] = true;

                (new UniqueMemberEmail($excludeFamilyId))->validate("member_texts.$index", $email, function ($message) use ($validator, $index) {
                    $validator->errors()->add("member_texts.$index", $message);
                });
            }
        });
    }
}
