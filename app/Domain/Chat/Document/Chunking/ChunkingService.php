<?php

namespace App\Domain\Chat\Document\Chunking;

/**
 * Splits extracted document text into overlapping chunks for embedding.
 * Tuned by config/rag.php (chunk_size, overlap). Chunk size is approximated
 * in words (~1 token ≈ 0.75 words).
 */
class ChunkingService
{
    /**
     * Chunk a list of pages, preserving page numbers.
     *
     * @param  array<int, array{page: int, text: string}>  $pages
     * @return array<int, array{content: string, page: ?int, section: ?string, token_count: int}>
     */
    public function chunkPages(array $pages): array
    {
        $chunks = [];
        $index = 0;

        foreach ($pages as $page) {
            foreach ($this->chunkText($page['text']) as $piece) {
                $piece['page'] = $page['page'] ?? null;
                $piece['chunk_index'] = $index++;
                $chunks[] = $piece;
            }
        }

        return $chunks;
    }

    /**
     * Chunk a single block of text.
     *
     * @return array<int, array{content: string, page: ?int, section: ?string, token_count: int}>
     */
    public function chunkText(string $text): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '') {
            return [];
        }

        $size = max(50, (int) config('rag.chunking.chunk_size', 512));
        $overlap = max(0, (int) config('rag.chunking.overlap', 50));

        $words = explode(' ', $text);
        $total = count($words);

        // Chunk size/overlap are configured in tokens; convert to a word window.
        $window = (int) ceil($size * 0.75);
        $step = max(1, $window - (int) ceil($overlap * 0.75));

        $chunks = [];
        for ($start = 0; $start < $total; $start += $step) {
            $slice = array_slice($words, $start, $window);
            if (empty($slice)) {
                break;
            }

            $content = implode(' ', $slice);
            $chunks[] = [
                'content' => $content,
                'page' => null,
                'section' => null,
                'token_count' => (int) ceil(count($slice) / 0.75),
            ];

            if ($start + $window >= $total) {
                break;
            }
        }

        return $chunks;
    }
}
