<?php

declare(strict_types=1);

namespace App\Domain\Chat\Tools;

use App\Domain\Chat\Contracts\ChatToolInterface;
use App\Domain\Chat\DataObjects\ToolExecution;
use App\Domain\User\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * The set of actions the student AI chat can take via function calling.
 *
 * Tools are only offered to students (the faculty AI assistant runs the same
 * RAG pipeline without tools), and every execution funnels through domain
 * services so their authorization rules bind AI-initiated actions too.
 * Failures never bubble out — they become error results the model can relay.
 */
class ChatToolRegistry
{
    /** @var array<string, ChatToolInterface> */
    private array $tools = [];

    public function __construct(
        GetUpcomingDeadlinesTool $deadlines,
        ListMyCoursesTool $courses,
        ListOfficeHourSlotsTool $officeHourSlots,
        BookOfficeHourSlotTool $bookOfficeHour,
        CancelOfficeHourBookingTool $cancelOfficeHour,
        GeneratePracticeQuizTool $practiceQuiz,
        GenerateFlashcardDeckTool $flashcardDeck,
        CreateStudyTaskTool $studyTask,
    ) {
        foreach ([
            $deadlines, $courses, $officeHourSlots, $bookOfficeHour,
            $cancelOfficeHour, $practiceQuiz, $flashcardDeck, $studyTask,
        ] as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    /**
     * OpenAI-format tool definitions available to this user ([] when the
     * user's chat should run without tools).
     *
     * @return array<int, array<string, mixed>>
     */
    public function definitionsFor(User $user): array
    {
        if (! $user->isStudent()) {
            return [];
        }

        return array_values(array_map(fn (ChatToolInterface $tool) => [
            'type' => 'function',
            'function' => [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'parameters' => $tool->parameters(),
            ],
        ], $this->tools));
    }

    /**
     * The human-facing progress label for a tool, e.g. "Booking office hours".
     */
    public function labelFor(string $name): string
    {
        return isset($this->tools[$name]) ? $this->tools[$name]->label() : 'Running a tool';
    }

    /**
     * Run a tool for a user. Expected failures (aborts from the underlying
     * services — 403/404/409/422) and unexpected exceptions both come back as
     * error executions so the model can explain the problem to the user.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function execute(User $user, string $name, array $arguments): ToolExecution
    {
        $tool = $this->tools[$name] ?? null;

        if ($tool === null || ! $user->isStudent()) {
            return new ToolExecution(
                name: $name,
                label: 'Unavailable tool',
                status: ToolExecution::STATUS_ERROR,
                summary: "The tool \"{$name}\" is not available.",
            );
        }

        try {
            $outcome = $tool->execute($user, $arguments);

            return new ToolExecution(
                name: $tool->name(),
                label: $tool->label(),
                status: ToolExecution::STATUS_OK,
                summary: $outcome['summary'],
                data: $outcome['data'],
                link: $outcome['link'] ?? null,
                linkLabel: $outcome['linkLabel'] ?? null,
            );
        } catch (HttpException $e) {
            return new ToolExecution(
                name: $tool->name(),
                label: $tool->label(),
                status: ToolExecution::STATUS_ERROR,
                summary: $e->getMessage() !== '' ? $e->getMessage() : 'The action was not allowed.',
            );
        } catch (Throwable $e) {
            report($e);

            return new ToolExecution(
                name: $tool->name(),
                label: $tool->label(),
                status: ToolExecution::STATUS_ERROR,
                summary: 'The tool failed unexpectedly. Do not retry; apologise and suggest doing it manually.',
            );
        }
    }
}
