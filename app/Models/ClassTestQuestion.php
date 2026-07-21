<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $class_test_id
 * @property string $type
 * @property string $question_text
 * @property array<int, array{key: string, text: string}>|null $options
 * @property string $correct_answer
 * @property int $marks
 * @property int $position
 */
class ClassTestQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_test_id',
        'type',
        'question_text',
        'options',
        'correct_answer',
        'marks',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'class_test_id' => 'integer',
            'options' => 'array',
            'marks' => 'integer',
            'position' => 'integer',
        ];
    }

    public function classTest(): BelongsTo
    {
        return $this->belongsTo(ClassTest::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ClassTestAnswer::class, 'question_id');
    }
}
