<?php

namespace Laravel\Ai\JsonSchema;

use Illuminate\JsonSchema\Types\Type;

/**
 * Passes through a JSON Schema document array without Illuminate's Serializer.
 */
class RawJsonSchemaType extends Type
{
    /**
     * @param  array<string, mixed>  $definition
     */
    public function __construct(protected array $definition) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->definition;
    }
}
