<?php

declare(strict_types=1);

namespace App\Domain\Chat\Contracts;

use App\Domain\User\Models\User;

/**
 * An action the AI chat assistant can take on the user's behalf via LLM
 * function calling — looking up real academic data (deadlines, office-hour
 * slots) or performing an operation (booking a slot, generating a quiz).
 *
 * Tools receive the authenticated user and delegate to the same domain
 * services the regular UI uses, so every authorization rule those services
 * enforce applies identically to AI-initiated actions.
 */
interface ChatToolInterface
{
    /**
     * Function name exposed to the model, e.g. "book_office_hour_slot".
     */
    public function name(): string;

    /**
     * Short human-facing label shown in the chat UI while the tool runs,
     * e.g. "Booking office hours".
     */
    public function label(): string;

    /**
     * What the tool does and when the model should call it.
     */
    public function description(): string;

    /**
     * JSON Schema of the arguments object (OpenAI function-calling format).
     *
     * @return array<string, mixed>
     */
    public function parameters(): array;

    /**
     * Run the tool. Throw an HttpException (via abort()) for expected
     * failures — the registry converts it into an error result the model
     * can relay to the user.
     *
     * @param  array<string, mixed>  $arguments  Model-supplied, already-decoded arguments.
     * @return array{data: array<string, mixed>, summary: string, link?: ?string, linkLabel?: ?string}
     *                                                                                                 data = payload returned to the model; summary = one-line human recap.
     */
    public function execute(User $user, array $arguments): array;
}
