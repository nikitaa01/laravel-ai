<?php

namespace Laravel\Ai;

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Contracts\HasJsonSchemaDocument;
use Laravel\Ai\Contracts\HasStructuredOutput;

final class StructuredOutputSchema
{
    /**
     * Resolve structured output schema from an agent (document-based or property map).
     */
    public static function forAgent(object $agent): ?Schema
    {
        if ($agent instanceof HasJsonSchemaDocument) {
            return new DocumentSchema($agent->jsonSchemaDocument());
        }

        if ($agent instanceof HasStructuredOutput) {
            return new ObjectSchema($agent->schema(new JsonSchemaTypeFactory));
        }

        return null;
    }
}
