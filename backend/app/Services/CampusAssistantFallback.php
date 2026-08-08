<?php

namespace App\Services;

class CampusAssistantFallback
{
    public function answer(string $question, array $context): array
    {
        $normalized = strtolower($question);
        $name = $context['name'] ?: 'Student';
        $courses = collect($context['courses'] ?? [])->filter(fn ($course) => ! empty($course['code']));
        $courseList = $courses->map(fn ($course) => $course['code'].' - '.$course['title'])->implode(', ');
        $codes = $courses->take(5)->pluck('code')->implode(', ');
        $attendance = $context['attendance'];
        $cgpa = $context['cgpa'];

        if ($this->contains($normalized, ['study plan', 'weekly plan', 'this week', 'routine'])) {
            $answer = "{$name}, here is a practical weekly plan".($codes ? " for {$codes}" : '').":

".
                "1. Start by listing this week's lectures, assignments, quizzes, and unfinished topics. Mark the two most urgent tasks.
".
                "2. Sunday and Monday: review lecture notes and create short summaries. Use two focused 45-minute sessions with a 10-minute break.
".
                "3. Tuesday and Wednesday: practise programming, mathematics, or course problems. Do not only reread—solve without looking at the answer first.
".
                "4. Thursday: complete assignments and revisit every mistake you made during practice.
".
                "5. Friday: take a 30-minute self-test, check weak areas, and ask your teacher or classmates about anything still unclear.
".
                "6. Saturday: revise the full week and prepare a short task list for the next one.

".
                "Keep the plan realistic: three completed priorities are better than ten unfinished ones. Adjust the time blocks around your published class routine.";
        } elseif ($this->contains($normalized, ['attendance', 'present', 'absent', 'class miss'])) {
            $status = $attendance === null
                ? 'Your campus profile does not yet contain enough attendance records to calculate a percentage.'
                : "Your currently recorded attendance is {$attendance}%.";
            $answer = "{$status}

".
                "To improve it, attend every scheduled class, arrive before attendance is taken, and review the Campus Services routine each evening. Keep a weekly record of missed classes and collect the missed notes within 24 hours. If illness or another valid issue prevents attendance, notify the relevant teacher early and follow the official department process.

".
                "A useful target is consistency rather than last-minute recovery. Check your percentage each week and take action before it approaches your institution's minimum requirement.";
        } elseif ($this->contains($normalized, ['progress', 'cgpa', 'result', 'performance'])) {
            $academic = $cgpa === null ? 'A current CGPA has not been recorded yet.' : "Your latest recorded CGPA is {$cgpa}.";
            $attendanceText = $attendance === null ? 'Attendance data is also not available yet.' : "Your recorded attendance is {$attendance}%.";
            $answer = "{$name}, here is the academic information currently available:

".
                "- {$academic}
- {$attendanceText}
".
                ($courseList ? "- Current courses: {$courseList}.
" : "- No current course enrollment was found.
").
                "
For a stronger progress review, compare quiz, assignment, midterm, and final results course by course. Identify the two lowest areas, practise them three times per week, and review progress after seven days. Add missing assessment and attendance records so future guidance can be more specific.";
        } elseif ($this->contains($normalized, ['improve', 'weak', 'better result', 'academic area'])) {
            $answer = "Begin with evidence instead of guessing. Compare your recent quiz, assignment, attendance, and exam results for each course".($codes ? " ({$codes})" : '').". The lowest repeated score usually identifies the area that needs attention first.

".
                "Use this improvement cycle:
1. Choose one weak topic.
2. Review the core concept for 20 minutes.
3. Solve examples without copying.
4. Check mistakes and write why they happened.
5. Repeat with a new problem two days later.

".
                "Also protect class attendance and sleep, because inconsistent attendance and rushed study reduce performance across every subject. Ask the course teacher for specific feedback when your saved academic data is incomplete.";
        } elseif ($this->contains($normalized, ['programming', 'coding', 'c++', 'java', 'algorithm', 'database', 'web development'])) {
            $answer = "Use an active learning approach for technical subjects:

".
                "1. Understand the concept in plain language before memorizing syntax.
".
                "2. Type and run a small example yourself.
".
                "3. Change the example and predict the output before running it.
".
                "4. Solve one easy and one medium problem without copying.
".
                "5. Record errors and their causes in a debugging notebook.

".
                "For programming, daily 30–60 minute practice is more effective than one long weekly session. If you share the exact topic or code error, the live AI provider can give a more targeted explanation; campus-specific marks and courses will still come only from your saved records.";
        } else {
            $answer = "{$name}, I can help with academic progress, attendance, enrolled courses, study planning, programming concepts, exam preparation, and productivity. ".
                ($courseList ? "Your saved current courses are {$courseList}. " : '').
                "Please ask one specific question and include the topic, what you already understand, and where you are stuck. That will produce a much more useful step-by-step answer.

".
                "The live AI provider is currently unavailable or rate-limited, so this response is generated safely from campus data and built-in academic guidance. It will automatically return to the live AI model when the provider becomes available.";
        }

        return ['answer' => $answer, 'model' => 'campus-data-fallback', 'fallback' => true];
    }

    private function contains(string $question, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($question, $keyword)) {
                return true;
            }
        }

        return false;
    }
}