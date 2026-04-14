<?php

namespace Laravel\Ai\Contracts\Gateway;

use Closure;
use Generator;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Responses\TextResponse;
use Laravel\Ai\Schema;

interface TextGateway
{
    /**
     * Generate text representing the next message in a conversation.
     *
     * @param  Message[]  $messages
     * @param  Tool[]  $tools
     */
    public function generateText(
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages = [],
        array $tools = [],
        ?Schema $schema = null,
        ?TextGenerationOptions $options = null,
        ?int $timeout = null,
    ): TextResponse;

    /**
     * Stream text representing the next message in a conversation.
     *
     * @param  Message[]  $messages
     * @param  Tool[]  $tools
     */
    public function streamText(
        string $invocationId,
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages = [],
        array $tools = [],
        ?Schema $schema = null,
        ?TextGenerationOptions $options = null,
        ?int $timeout = null,
    ): Generator;

    /**
     * Specify callbacks that should be invoked when tools are invoking / invoked.
     */
    public function onToolInvocation(Closure $invoking, Closure $invoked): self;
}
