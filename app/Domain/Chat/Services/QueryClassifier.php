<?php

declare(strict_types=1);

namespace App\Domain\Chat\Services;

use App\Enums\QueryIntent;

/**
 * Cheap, deterministic intent gate that runs before any embedding/retrieval/LLM
 * work. Its only job is to peel off conversational filler (greetings, thanks,
 * "what can you do") so those never trigger an API call. Anything it is unsure
 * about is treated as ACADEMIC — i.e. the expensive-but-correct default.
 */
class QueryClassifier
{
    /**
     * Greeting / acknowledgement phrases. A message counts as small talk only
     * when it is short AND made up of these — "hello, explain binary trees" is
     * a real question that merely opens with a greeting.
     *
     * @var array<int, string>
     */
    private const GREETINGS = [
        'hi', 'hii', 'hiii', 'hiya', 'hey', 'heya', 'hello', 'helo', 'yo', 'sup',
        'whats up', 'what is up', 'how are you', 'how are u', 'how r u', 'hows it going',
        'how is it going', 'how do you do', 'good morning', 'good afternoon',
        'good evening', 'good day', 'greetings', 'morning', 'evening',
        'assalamualaikum', 'salam', 'namaste',
    ];

    /**
     * @var array<int, string>
     */
    private const THANKS = [
        'thanks', 'thank you', 'thank u', 'thx', 'tysm', 'ty', 'thankyou',
        'much appreciated', 'appreciate it', 'cheers', 'great thanks', 'thanks a lot',
    ];

    /**
     * @var array<int, string>
     */
    private const FAREWELLS = [
        'bye', 'goodbye', 'good bye', 'see you', 'see ya', 'cya', 'good night',
        'goodnight', 'gn', 'take care', 'later', 'talk later',
    ];

    /**
     * Bare acknowledgements that carry no question.
     *
     * @var array<int, string>
     */
    private const ACKS = [
        'ok', 'okay', 'okk', 'k', 'cool', 'nice', 'great', 'awesome', 'perfect',
        'got it', 'understood', 'fine', 'alright', 'lol', 'haha', 'good', 'wow',
    ];

    /**
     * Meta / capability questions answerable from a fixed blurb.
     *
     * @var array<int, string>
     */
    private const META = [
        'who are you', 'what are you', 'what is your name', 'whats your name',
        'what can you do', 'what do you do', 'how can you help', 'how do you work',
        'what is unigpt', 'whats unigpt', 'who made you', 'are you a bot',
        'are you ai', 'are you an ai', 'help', 'what are your features',
    ];

    public function classify(string $message): QueryIntent
    {
        $normalized = $this->normalize($message);

        if ($normalized === '') {
            return QueryIntent::ACADEMIC;
        }

        $wordCount = count(explode(' ', $normalized));

        // Meta questions can be slightly longer ("what can you do for me").
        if ($wordCount <= 8 && $this->matches($normalized, self::META)) {
            return QueryIntent::META;
        }

        // Small talk must be short; a longer message almost always carries a real
        // request even if it opens with a greeting.
        if ($wordCount <= 5 && $this->matches($normalized, [
            ...self::GREETINGS, ...self::THANKS, ...self::FAREWELLS, ...self::ACKS,
        ])) {
            return QueryIntent::SMALLTALK;
        }

        return QueryIntent::ACADEMIC;
    }

    /**
     * A friendly, deterministic reply for a non-academic message. Returns null
     * for academic messages (which must go through the RAG pipeline instead).
     */
    public function reply(string $message, ?string $userName = null): ?string
    {
        $intent = $this->classify($message);
        $name = $userName ? ' '.trim(explode(' ', trim($userName))[0]) : '';
        $normalized = $this->normalize($message);

        return match ($intent) {
            QueryIntent::META => 'I\'m UniGPT, your university academic copilot. I can answer questions about '
                .'your courses and university documents (grounded in approved materials with citations), '
                .'explain academic concepts, help you prepare for exams and assignments, and point you to '
                .'study resources. What would you like help with today?',
            QueryIntent::SMALLTALK => $this->smalltalkReply($normalized, $name),
            QueryIntent::ACADEMIC => null,
        };
    }

    private function smalltalkReply(string $normalized, string $name): string
    {
        if ($this->matches($normalized, self::THANKS)) {
            return "You're welcome{$name}! Happy to help — ask me anything about your courses or studies whenever you're ready.";
        }

        if ($this->matches($normalized, self::FAREWELLS)) {
            return "Goodbye{$name}! Come back any time you need a hand with your coursework. Good luck with your studies!";
        }

        if ($this->matches($normalized, self::ACKS)) {
            return "Great{$name}! Let me know if there's anything about your courses, assignments or exams I can help with.";
        }

        return "Hello{$name}! I'm UniGPT, your academic assistant. I can help with your courses, "
            .'explain concepts, summarise university documents, and prep you for exams and assignments. '
            .'What can I help you with?';
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function matches(string $normalized, array $needles): bool
    {
        foreach ($needles as $needle) {
            // Whole-message match, or the message is exactly a needle plus light
            // trailing filler (e.g. "hi there", "thanks so much").
            if ($normalized === $needle || str_starts_with($normalized.' ', $needle.' ')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lowercase, strip punctuation/emoji, collapse whitespace.
     */
    private function normalize(string $message): string
    {
        $message = mb_strtolower(trim($message));
        $message = preg_replace('/[^a-z0-9\s]/u', ' ', $message) ?? $message;
        $message = preg_replace('/\s+/', ' ', $message) ?? $message;

        return trim($message);
    }
}
