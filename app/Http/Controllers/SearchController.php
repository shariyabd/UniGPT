<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Search\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Global ⌘K search across the user's accessible content — semantic over the
 * RAG corpus, lexical over courses/assignments/discussions/chat history.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $search): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        return response()->json([
            'groups' => $search->search($request->user(), $validated['q']),
        ]);
    }
}
