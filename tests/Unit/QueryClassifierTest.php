<?php

namespace Tests\Unit;

use App\Domain\Chat\Services\QueryClassifier;
use App\Enums\QueryIntent;
use PHPUnit\Framework\TestCase;

class QueryClassifierTest extends TestCase
{
    private QueryClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new QueryClassifier;
    }

    /**
     * @dataProvider smalltalkProvider
     */
    public function test_greetings_and_filler_are_small_talk(string $message): void
    {
        $this->assertSame(QueryIntent::SMALLTALK, $this->classifier->classify($message));
        $this->assertNotNull($this->classifier->reply($message));
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function smalltalkProvider(): array
    {
        return [
            ['Hi'], ['hello'], ['Hey there'], ['Good morning'], ['how are you?'],
            ['Thanks'], ['thank you so much'], ['bye'], ['good night'], ['ok'], ['cool'],
        ];
    }

    /**
     * @dataProvider metaProvider
     */
    public function test_capability_questions_are_meta(string $message): void
    {
        $this->assertSame(QueryIntent::META, $this->classifier->classify($message));
        $this->assertNotNull($this->classifier->reply($message));
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function metaProvider(): array
    {
        return [
            ['who are you'], ['what can you do'], ['what is UniNexus?'], ['How can you help me?'],
        ];
    }

    /**
     * @dataProvider academicProvider
     */
    public function test_real_questions_are_academic(string $message): void
    {
        $this->assertSame(QueryIntent::ACADEMIC, $this->classifier->classify($message));
        // Academic messages must fall through to the RAG pipeline (no canned reply).
        $this->assertNull($this->classifier->reply($message));
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function academicProvider(): array
    {
        return [
            ['I need to learn Data Structures'],
            ['Explain binary search trees'],
            ['hello, can you explain merge sort to me'], // opens with a greeting but is a real question
            ['What is the deadline for the DSA assignment?'],
            ['Summarize the operating systems lecture notes'],
        ];
    }
}
