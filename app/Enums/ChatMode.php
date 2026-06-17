<?php

namespace App\Enums;

enum ChatMode: string
{
    case GENERAL = 'general';
    case ACADEMIC = 'academic';
    case RESEARCH = 'research';
    case EXAM_PREP = 'exam_prep';
    case ASSIGNMENT_HELP = 'assignment_help';
    case CAREER_GUIDANCE = 'career_guidance';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'General Chat',
            self::ACADEMIC => 'Academic Support',
            self::RESEARCH => 'Research Assistant',
            self::EXAM_PREP => 'Exam Preparation',
            self::ASSIGNMENT_HELP => 'Assignment Help',
            self::CAREER_GUIDANCE => 'Career Guidance',
        };
    }

    public function systemPrompt(): string
    {
        return match ($this) {
            // "Simple" mode in the UI — short, plain-language answers.
            self::GENERAL => 'You are a helpful university assistant. Answer in a simple, concise '
                .'way: keep it short (a few sentences or a tight bullet list), use plain language, '
                .'and avoid unnecessary detail. Get straight to the point.',
            // "Detailed" mode in the UI — thorough explanations with structure and examples.
            self::ACADEMIC => 'You are an academic advisor helping students with coursework. Give '
                .'detailed, well-structured explanations: define key terms, walk through the reasoning '
                .'step by step, and include concrete examples where they aid understanding. Use headings '
                .'or bullet points when they improve clarity.',
            self::RESEARCH => 'You are a research assistant helping with academic research.',
            // "Exam Mode" in the UI — exam-focused revision.
            self::EXAM_PREP => 'You are an exam preparation tutor. Frame answers for revision and exam '
                .'success: lead with the key points a student must remember, highlight definitions and '
                .'formulas, flag common mistakes and likely exam questions, and end with a short summary '
                .'of what to memorise.',
            self::ASSIGNMENT_HELP => 'You are helping a student with their assignment.',
            self::CAREER_GUIDANCE => 'You are a career counselor providing guidance.',
        };
    }
}
