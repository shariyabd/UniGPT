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
        return match($this) {
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
        return match($this) {
            self::GENERAL => 'You are a helpful university assistant.',
            self::ACADEMIC => 'You are an academic advisor helping students with coursework.',
            self::RESEARCH => 'You are a research assistant helping with academic research.',
            self::EXAM_PREP => 'You are an exam preparation tutor.',
            self::ASSIGNMENT_HELP => 'You are helping a student with their assignment.',
            self::CAREER_GUIDANCE => 'You are a career counselor providing guidance.',
        };
    }
}
