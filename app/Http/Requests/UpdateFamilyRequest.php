<?php

namespace App\Http\Requests;

use App\Rules\UniqueMemberEmail;
use App\Support\MemberTextParser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateFamilyRequest extends FormRequest
{
    /** Một family YouTube Premium chỉ chứa được 5 thành viên. */
    private const MAX_MEMBERS = 5;
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
            'next_payment_at' => ['nullable', 'date'],
            'number_phone' => ['nullable', 'string'],
            'afiilicate_by' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'bill_of_master' => ['array'],
            'bill_of_master.*' => ['file', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx', 'max:10240'],
            'bill_payment' => ['array'],
            'bill_payment.*' => ['file', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx', 'max:10240'],            'member_texts' => ['array'],
            'member_texts.*' => ['nullable', 'string'],
            'member_ids' => ['array'],
            'member_ids.*' => ['nullable', 'integer'],
        ];
    }

    /** Mặc định Laravel báo bằng tiếng Anh, mà bill là chỗ người dùng hay gặp lỗi nhất. */
    public function messages(): array
    {
        return [
            'bill_of_master.*.mimes' => 'Bill gốc chỉ nhận ảnh (jpg, png, gif, webp), PDF hoặc Word.',
            'bill_of_master.*.max' => 'Bill gốc không được lớn hơn 10MB.',
            'bill_payment.*.mimes' => 'Bill thanh toán chỉ nhận ảnh (jpg, png, gif, webp), PDF hoặc Word.',
            'bill_payment.*.max' => 'Bill thanh toán không được lớn hơn 10MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $excludeFamilyId = (int) $this->route('family')->id;

        $validator->after(function (Validator $validator) use ($excludeFamilyId) {
            $filled = collect((array) $this->input('member_texts', []))
                ->filter(fn ($text) => trim((string) $text) !== '')
                ->count();

            if ($filled > self::MAX_MEMBERS) {
                $validator->errors()->add(
                    'member_texts',
                    'Một family chỉ chứa được '.self::MAX_MEMBERS.' thành viên, danh sách đang có '.$filled.'.',
                );
            }

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
