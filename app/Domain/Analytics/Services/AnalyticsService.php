<?php

namespace App\Domain\Analytics\Services;

use App\Domain\User\Models\User;
use App\Enums\DocumentStatus;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Department;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates platform-wide analytics from real data for the admin dashboards.
 */
class AnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'totalUsers' => User::count(),
            'activeUsers' => User::where('is_active', true)->count(),
            'totalQueries' => ChatMessage::where('role', 'user')->count(),
            'totalDocuments' => Document::where('status', DocumentStatus::APPROVED->value)->count(),
            'totalSessions' => ChatSession::count(),
            'savedAnswers' => DB::table('saved_answers')->count(),
        ];
    }

    /**
     * Query counts grouped by the asking user's department.
     *
     * @return array<int, array<string, mixed>>
     */
    public function departmentBreakdown(): array
    {
        $total = ChatMessage::where('role', 'user')->count() ?: 1;

        return Department::query()
            ->leftJoin('users', 'users.department_id', '=', 'departments.id')
            ->leftJoin('chat_sessions', 'chat_sessions.user_id', '=', 'users.id')
            ->leftJoin('chat_messages', function ($join) {
                $join->on('chat_messages.chat_session_id', '=', 'chat_sessions.id')
                    ->where('chat_messages.role', '=', 'user');
            })
            ->groupBy('departments.id', 'departments.name')
            ->select('departments.name', DB::raw('COUNT(chat_messages.id) as queries'))
            ->orderByDesc('queries')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'queries' => (int) $row->queries,
                'percentage' => round(((int) $row->queries / $total) * 100, 1),
            ])
            ->all();
    }

    /**
     * Most frequent user questions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function topQueries(int $limit = 8): array
    {
        return ChatMessage::where('role', 'user')
            ->select('content', DB::raw('COUNT(*) as count'))
            ->groupBy('content')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'query' => $row->content,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function usersByRole(): array
    {
        return DB::table('roles')
            ->leftJoin('role_user', 'role_user.role_id', '=', 'roles.id')
            ->groupBy('roles.id', 'roles.name')
            ->select('roles.name', DB::raw('COUNT(role_user.user_id) as count'))
            ->get()
            ->map(fn ($row) => ['role' => $row->name, 'count' => (int) $row->count])
            ->all();
    }
}
