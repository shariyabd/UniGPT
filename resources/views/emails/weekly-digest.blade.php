@extends('emails.layout')

@section('content')
    <h1 style="margin:0 0 8px;font-size:22px;color:#111827;">Hi {{ $student->name }},</h1>
    <p style="margin:0 0 24px;font-size:14px;color:#4b5563;">Here's your week ahead at a glance.</p>

    @if (!empty($digest['deadlines']))
        <h2 style="margin:0 0 12px;font-size:16px;color:#111827;">📅 Deadlines this week</h2>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
            @foreach ($digest['deadlines'] as $deadline)
                <tr>
                    <td style="padding:8px 12px;border-left:3px solid #6366f1;background:#f9fafb;border-radius:6px;font-size:14px;">
                        <strong>{{ $deadline['title'] }}</strong>
                        @if ($deadline['course']) <span style="color:#6b7280;">({{ $deadline['course'] }})</span> @endif
                        <br>
                        <span style="color:#6b7280;">{{ ucfirst(str_replace('-', ' ', $deadline['type'])) }} · due {{ $deadline['date'] }}</span>
                    </td>
                </tr>
                <tr><td style="height:6px;"></td></tr>
            @endforeach
        </table>
    @endif

    @if (!empty($digest['grades']))
        <h2 style="margin:0 0 12px;font-size:16px;color:#111827;">🎓 Grades posted</h2>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
            @foreach ($digest['grades'] as $grade)
                <tr>
                    <td style="padding:8px 12px;border-left:3px solid #10b981;background:#f9fafb;border-radius:6px;font-size:14px;">
                        <strong>{{ $grade['assignment'] }}</strong>
                        @if ($grade['course']) <span style="color:#6b7280;">({{ $grade['course'] }})</span> @endif
                        — {{ $grade['grade'] }}/{{ $grade['total'] }}
                    </td>
                </tr>
                <tr><td style="height:6px;"></td></tr>
            @endforeach
        </table>
    @endif

    @if (!empty($digest['officeHours']))
        <h2 style="margin:0 0 12px;font-size:16px;color:#111827;">🕐 Booked office hours</h2>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
            @foreach ($digest['officeHours'] as $meeting)
                <tr>
                    <td style="padding:8px 12px;border-left:3px solid #f59e0b;background:#f9fafb;border-radius:6px;font-size:14px;">
                        <strong>{{ $meeting['faculty'] }}</strong> — {{ $meeting['when'] }}
                        @if ($meeting['location']) <span style="color:#6b7280;">· {{ $meeting['location'] }}</span> @endif
                    </td>
                </tr>
                <tr><td style="height:6px;"></td></tr>
            @endforeach
        </table>
    @endif

    @if (($digest['flashcardsDue'] ?? 0) > 0)
        <p style="margin:0 0 24px;font-size:14px;color:#4b5563;">
            🃏 You have <strong>{{ $digest['flashcardsDue'] }}</strong> flashcard{{ $digest['flashcardsDue'] === 1 ? '' : 's' }} due for review.
        </p>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:8px;background:#6366f1;">
                <a href="{{ route('calendar') }}" style="display:inline-block;padding:10px 20px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                    Open your calendar
                </a>
            </td>
        </tr>
    </table>
@endsection
