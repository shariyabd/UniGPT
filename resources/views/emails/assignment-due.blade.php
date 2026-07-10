@extends('emails.layout')

@section('content')
    <h1 style="margin:0 0 8px;font-size:22px;color:#111827;">Hi {{ $student->name }},</h1>
    <p style="margin:0 0 20px;font-size:14px;color:#4b5563;">A quick heads-up — you haven't submitted this assignment yet:</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td style="padding:12px 16px;border-left:3px solid #ef4444;background:#f9fafb;border-radius:6px;font-size:14px;">
                <strong style="font-size:15px;">{{ $assignment->title }}</strong>
                @if ($assignment->course) <span style="color:#6b7280;">({{ $assignment->course->code }})</span> @endif
                <br>
                <span style="color:#b91c1c;font-weight:600;">Due {{ $assignment->due_at?->format('D, M j · g:i A') }}</span>
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:8px;background:#6366f1;">
                <a href="{{ route('assignments.show', $assignment->id) }}" style="display:inline-block;padding:10px 20px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                    Open the assignment
                </a>
            </td>
        </tr>
    </table>
@endsection
