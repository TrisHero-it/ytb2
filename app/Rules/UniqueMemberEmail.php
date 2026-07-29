<?php

namespace App\Rules;

use App\Models\Family;
use App\Models\FamilyMember;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueMemberEmail implements ValidationRule
{
    public function __construct(private readonly ?int $excludeFamilyId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim((string) $value));

        if ($email === '') {
            return;
        }

        $ownerQuery = Family::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email]);
        if ($this->excludeFamilyId !== null) {
            $ownerQuery->where('id', '!=', $this->excludeFamilyId);
        }

        if ($ownerQuery->exists()) {
            $fail("Email {$value} đã tồn tại ở family khác.");
            return;
        }

        $memberQuery = FamilyMember::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email]);
        if ($this->excludeFamilyId !== null) {
            $memberQuery->where('family_id', '!=', $this->excludeFamilyId);
        }

        if ($memberQuery->exists()) {
            $fail("Email {$value} đã tồn tại ở family khác.");
        }
    }
}
