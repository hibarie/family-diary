<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiaryEntry;
use App\Models\SchoolEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarController extends Controller
{
    /**
     * 指定した月のサマリーを返す(その月のうち、育児日記や園の予定がある日付と件数)
     */
    public function monthSummary(Request $request, int $year, int $month)
    {
        $user = $request->user();
        $familyId = $user->families()->first()->id;

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $diaryCounts = DiaryEntry::where('family_id', $familyId)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn ($entry) => $entry->entry_date->format('Y-m-d'))
            ->map->count();

        $eventCounts = SchoolEvent::where('family_id', $familyId)
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn ($event) => $event->event_date->format('Y-m-d'))
            ->map->count();

        $dates = $diaryCounts->keys()->merge($eventCounts->keys())->unique()->sort()->values();

        $days = $dates->map(function ($date) use ($diaryCounts, $eventCounts) {
            return [
                'date' => $date,
                'diary_count' => $diaryCounts->get($date, 0),
                'school_event_count' => $eventCounts->get($date, 0),
            ];
        });

        return response()->json([
            'year' => $year,
            'month' => $month,
            'days' => $days,
        ]);
    }

    /**
     * 指定した日の育児日記・園の予定をまとめて返す
     */
    public function day(Request $request, string $date)
    {
        $request->merge(['date' => $date]);
        $request->validate([
            'date' => 'date_format:Y-m-d',
        ]);

        $user = $request->user();
        $familyId = $user->families()->first()->id;

        $diaryEntries = DiaryEntry::with('media')
            ->where('family_id', $familyId)
            ->whereDate('entry_date', $date)
            ->orderBy('created_at')
            ->get();

        $schoolEvents = SchoolEvent::where('family_id', $familyId)
            ->whereDate('event_date', $date)
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'date' => $date,
            'diary_entries' => $diaryEntries,
            'school_events' => $schoolEvents,
        ]);
    }
}
