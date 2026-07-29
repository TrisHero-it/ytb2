<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Support\MemberTextParser;
use Illuminate\Console\Command;

class CutoverBackfillMembers extends Command
{
    protected $signature = 'cutover:backfill-members';

    protected $description = 'Parse families.member1..member5 into family_members rows.';

    private const MEMBER_COLUMNS = ['member1', 'member2', 'member3', 'member4', 'member5'];

    public function handle(): int
    {
        $familiesProcessed = 0;
        $familiesSkipped = 0;
        $membersCreated = 0;

        foreach (Family::query()->get() as $family) {
            if ($family->members()->exists()) {
                $familiesSkipped++;

                continue;
            }

            $familiesProcessed++;

            foreach (self::MEMBER_COLUMNS as $column) {
                $raw = $family->getAttribute($column);
                if ($raw === null) {
                    continue;
                }

                $parsed = MemberTextParser::parse($raw);
                FamilyMember::create(array_merge($parsed, ['family_id' => $family->id]));
                $membersCreated++;
            }
        }

        $this->info("Families processed: {$familiesProcessed}");
        $this->info("Families skipped (already had members): {$familiesSkipped}");
        $this->info("Members created: {$membersCreated}");

        return self::SUCCESS;
    }
}
