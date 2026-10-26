<?php

namespace Database\Seeders;

use App\Models\Child;
use App\Models\DiaryEntry;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\SchoolEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * 公開デモ用のデータ。
 * ポートフォリオを見た人が「demo@example.com / password」でログインして触れるようにする。
 * 実在の家族の名前や写真は使わず、架空のデータだけを入れる。
 * すでにデモユーザーがいる場合は何もしない（起動のたびに増えないように）。
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'demo@example.com')->exists()) {
            return;
        }

        $user = User::create([
            'name' => 'デモユーザー',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
        ]);

        $family = Family::create([
            'name' => 'デモ家族',
            'invite_code' => 'DEMO2026',
        ]);
        $family->users()->attach($user->id, ['role' => 'owner']);

        $papa = FamilyMember::create([
            'family_id' => $family->id,
            'name' => 'たろう',
            'role' => 'パパ',
            'birth_date' => '1990-04-10',
        ]);
        $mama = FamilyMember::create([
            'family_id' => $family->id,
            'name' => 'はなこ',
            'role' => 'ママ',
            'birth_date' => '1991-08-22',
        ]);

        $child = Child::create([
            'family_id' => $family->id,
            'name' => 'さくら',
            'birth_date' => now()->subYears(4)->subMonths(3)->toDateString(),
        ]);

        $today = Carbon::today();

        $diaries = [
            [0, '公園でブランコを一人でこげるようになった！帰り道もずっとその話をしていた。'],
            [-1, '保育園で作ったどんぐりのネックレスを持って帰ってきた。'],
            [-3, '初めてカレーを「辛くない」と言って完食。'],
            [-6, '雨の日。家でお絵かきとパズルをして過ごした。'],
        ];
        foreach ($diaries as [$offset, $body]) {
            DiaryEntry::create([
                'family_id' => $family->id,
                'user_id' => $user->id,
                'body' => $body,
                'entry_date' => $today->copy()->addDays($offset)->toDateString(),
            ]);
        }

        $events = [
            ['child_id' => $child->id, 'title' => '遠足（お弁当）', 'event_type' => 'event', 'offset' => 3],
            ['child_id' => $child->id, 'title' => '体操服・上履き', 'event_type' => 'belongings', 'offset' => 1],
            ['child_id' => $child->id, 'title' => '個人面談', 'event_type' => 'meeting', 'offset' => 7],
            ['family_member_id' => $papa->id, 'title' => null, 'event_type' => 'work', 'offset' => 0],
            ['family_member_id' => $mama->id, 'title' => null, 'event_type' => 'off', 'offset' => 2],
        ];
        foreach ($events as $e) {
            SchoolEvent::create([
                'family_id' => $family->id,
                'child_id' => $e['child_id'] ?? null,
                'family_member_id' => $e['family_member_id'] ?? null,
                'title' => $e['title'],
                'event_type' => $e['event_type'],
                'event_date' => $today->copy()->addDays($e['offset'])->toDateString(),
            ]);
        }
    }
}
