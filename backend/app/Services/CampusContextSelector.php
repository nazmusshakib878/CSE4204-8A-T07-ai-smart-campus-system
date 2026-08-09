<?php

namespace App\Services;

class CampusContextSelector
{
    private const PATTERNS = [
        'profile' => '/(?:\bmy\s+(?:profile|name|department|program|semester|section|student\s*(?:id|number))\b|আমার\s+(?:প্রোফাইল|নাম|ডিপার্টমেন্ট|প্রোগ্রাম|সেমিস্টার|সেকশন))/iu',
        'courses' => '/(?:\b(?:my\s+)?(?:courses?|subjects?)\b|\benrolled\b|\bwhat\s+(?:courses?|classes?)\s+am\s+i\s+taking\b|কোর্স|বিষয়)/iu',
        'attendance' => '/(?:\battendance\b|\bpresent\b|\babsent\b|উপস্থিতি|হাজিরা)/iu',
        'results' => '/(?:\bresults?\b|\bgrades?\b|\bcgpa\b|\bgpa\b|\bmarks?\b|\bscores?\b|রেজাল্ট|ফলাফল|মার্ক)/iu',
        'schedule' => '/(?:\bschedule\b|\broutine\b|\bnext\s+class\b|\bclass\s+time\b|ক্লাস\s+কখন|রুটিন)/iu',
        'appointments' => '/(?:\bappointments?\b|\bmeetings?\b|অ্যাপয়েন্টমেন্ট|সাক্ষাৎ)/iu',
        'policies' => '/(?:\bpolic(?:y|ies)\b|\brequirements?\b|\bminimum\s+attendance\b|\beligib(?:le|ility)\b|নিয়ম|নীতি|সর্বনিম্ন)/iu',
    ];

    private const FOLLOW_UP_PATTERN = '/^(?:it|that|this|those|the\s+(?:first|second|last)\s+one|what\s+does\s+that|explain\s+more|why|how|এটা|ওটা|প্রথমটা|দ্বিতীয়টা)/iu';

    public function select(string $question, array $history = []): array
    {
        $text = trim($question);

        if (preg_match(self::FOLLOW_UP_PATTERN, $text) === 1) {
            $recent = collect($history)->take(-4)->pluck('content')->filter('is_string')->implode("\n");
            $text = $recent."\n".$text;
        }

        return collect(self::PATTERNS)
            ->filter(fn (string $pattern) => preg_match($pattern, $text) === 1)
            ->keys()
            ->values()
            ->all();
    }
}
