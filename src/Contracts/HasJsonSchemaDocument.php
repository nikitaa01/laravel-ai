<?php

namespace Laravel\Ai\Contracts;

/**
 * Agents that return a full JSON Schema document (e.g. with $defs and recursive $ref).
 * Prefer this over {@see HasStructuredOutput} when the schema cannot be expressed as a map of Illuminate JsonSchema types.
 */
interface HasJsonSchemaDocument
{
    /**
     * Root JSON Schema object, typically including "type": "object" and optional "$defs".
     *
     * @return array<string, mixed>
     */
    public function jsonSchemaDocument(): array;
}
