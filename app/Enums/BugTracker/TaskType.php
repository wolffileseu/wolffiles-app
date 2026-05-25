<?php

namespace App\Enums\BugTracker;

enum TaskType: string
{
    case Bug          = 'bug';
    case Feature      = 'feature';
    case Task         = 'task';
    case Improvement  = 'improvement';
    case Question     = 'question';

    public function label(): string
    {
        return match($this) {
            self::Bug          => 'Bug',
            self::Feature      => 'Feature Request',
            self::Task         => 'Task',
            self::Improvement  => 'Improvement',
            self::Question     => 'Question',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Bug          => '🐛',
            self::Feature      => '✨',
            self::Task         => '📋',
            self::Improvement  => '⚡',
            self::Question     => '❓',
        };
    }
}
