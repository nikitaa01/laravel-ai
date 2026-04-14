<?php

namespace Laravel\Ai;

use Laravel\Ai\JsonSchema\Concerns\NormalizesJsonSchema;
use Laravel\Ai\JsonSchema\RawJsonSchemaType;
use Prism\Prism\Contracts\HasSchemaType;

class DocumentSchema extends Schema implements HasSchemaType
{
    use NormalizesJsonSchema;

    /**
     * @param  array<string, mixed>  $schema  Full JSON Schema document (e.g. root object with optional $defs and $ref).
     */
    public function __construct(
        array $schema,
        string $name = 'schema_definition',
        bool $strict = true
    ) {
        parent::__construct(
            schema: new RawJsonSchemaType($schema),
            name: $name,
            strict: $strict
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(): array
    {
        return static::disableAdditionalProperties(parent::toSchema());
    }

    public function schemaType(): string
    {
        return 'object';
    }
}
