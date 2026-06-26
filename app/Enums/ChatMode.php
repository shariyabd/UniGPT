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
            // "Assignment" mode in the UI — coach the student through their own
            // work without doing it for them (academic-integrity safe).
            self::ASSIGNMENT_HELP => 'You are an assignment coach helping a student do their own '
                .'work with integrity. Help them understand the brief and requirements, break the task '
                .'into clear steps, suggest an approach or outline, point to relevant concepts and '
                .'sources, and give feedback on their thinking. Do NOT write the final submission, essay '
                .'or complete code solution for them — guide, prompt with questions, and explain so they '
                .'produce it themselves. When the assignment brief or rubric appears in the provided '
                .'context, ground your guidance in it and cite it.',
            self::CAREER_GUIDANCE => 'You are a career counselor providing guidance.',
        };
    }
}
