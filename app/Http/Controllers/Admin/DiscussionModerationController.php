<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin moderation queue for reported discussion posts and comments.
 */
class DiscussionModerationController extends Controller
{
    public function index(): Response
    {
        $reports = PostReport::open()
            ->with([
                'reporter:id,name',
                'post.author:id,name',
                'post.section.course:id,code',
                'comment.author:id,name',
                'comment.post.section.course:id,code',
            ])
            ->latest()
            ->paginate(20)
            ->through(fn (PostReport $report) => $this->present($report));

        return Inertia::render('Admin/DiscussionReports', [
            'reports' => $reports,
        ]);
    }

    public function resolve(Request $request, PostReport $report): RedirectResponse
    {
        $report->update([
            'status' => 'resolved',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Report dismissed.');
    }

    public function removeContent(Request $request, PostReport $report): RedirectResponse
    {
        // Soft-delete the offending content, then resolve the report.
        $report->post?->delete();
        $report->comment?->delete();

        $report->update([
            'status' => 'resolved',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Content removed and report resolved.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(PostReport $report): array
    {
        $target = $report->post ?? $report->comment;
        $section = $report->post?->section ?? $report->comment?->post?->section;

        return [
            'id' => $report->id,
            'type' => $report->post_id ? 'post' : 'comment',
            'reason' => $report->reason,
            'reportedAt' => $report->created_at?->diffForHumans(),
            'reporter' => $report->reporter?->name,
            'section' => $section?->course?->code ? trim($section->course->code.' · '.$section->label) : null,
            'author' => $target?->author?->name,
            'body' => $target?->body,
            'removed' => $target === null, // content already deleted elsewhere
        ];
    }
}
