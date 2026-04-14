<?php

namespace Laravel\Ai;

use Closure;
use Illuminate\Container\Container;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\StringType;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Agent;

/**
 * Get an ad-hoc agent instance.
 */
function agent(
    string $instructions = '',
    iterable $messages = [],
    iterable $tools = [],
    ?Closure $schema = null,
): Agent {
    return $schema
        ? new StructuredAnonymousAgent($instructions, $messages, $tools, $schema)
        : new AnonymousAgent($instructions, $messages, $tools);
}

/**
 * Get a new pipeline instance.
 */
function pipeline(): Pipeline
{
    return new Pipeline(Container::getInstance());
}

/**
 * Generate a new ULID.
 */
function ulid(): string
{
    return strtolower((string) Str::ulid());
}

/**
 * Generate fake data from a JSON schema type.
 */
function generate_fake_data_for_json_schema_type(Type $type): mixed
{
    $attributes = (fn () => get_object_vars($type))->call($type);

    if (isset($attributes['enum']) && is_array($attributes['enum']) && count($attributes['enum']) > 0) {
        $enumValue = $attributes['enum'][array_rand($attributes['enum'])];

        return $type::class === ArrayType::class
            ? [$enumValue]
            : $enumValue;
    }

    if (isset($attributes['default'])) {
        return $attributes['default'];
    }

    return match ($type::class) {
        ObjectType::class => (function () use ($attributes) {
            $result = [];

            foreach ($attributes['properties'] ?? [] as $key => $property) {
                $result[$key] = generate_fake_data_for_json_schema_type($property);
            }

            return $result;
        })(),

        ArrayType::class => (function () use ($attributes) {
            $min = $attributes['minItems'] ?? 1;
            $max = $attributes['maxItems'] ?? max($min, 3);

            $count = random_int($min, $max);

            if (! isset($attributes['items'])) {
                return [];
            }

            $result = [];

            for ($i = 0; $i < $count; $i++) {
                $result[] = generate_fake_data_for_json_schema_type(
                    $attributes['items']
                );
            }

            return $result;
        })(),

        StringType::class => (function () use ($attributes) {
            if (isset($attributes['format'])) {
                return match ($attributes['format']) {
                    'date' => date('Y-m-d'),
                    'date-time' => date('c'),
                    'email' => 'user@example.com',
                    'time' => date('H:i:s'),
                    'uri', 'url' => 'https://example.com',
                    'uuid' => (string) Str::uuid(),
                    default => 'string',
                };
            }

            $min = $attributes['minLength'] ?? 1;
            $max = $attributes['maxLength'] ?? max($min, 10);

            return Str::random(random_int($min, $max));
        })(),

        IntegerType::class => (function () use ($attributes) {
            $min = $attributes['minimum'] ?? 0;
            $max = $attributes['maximum'] ?? max($min, 100);

            return random_int($min, $max);
        })(),

        NumberType::class => (function () use ($attributes) {
            $min = $attributes['minimum'] ?? 0.0;
            $max = $attributes['maximum'] ?? max($min, 100.0);

            return $min + mt_rand() / mt_getrandmax() * ($max - $min);
        })(),

        BooleanType::class => random_int(0, 1) === 0,

        default => null,
    };
}

/**
 * Generate fake data from a JSON Schema document array (supports $ref to #/$defs/...).
 *
 * @param  array<string, mixed>  $schema
 * @param  array<string, mixed>|null  $root
 * @return array<string, mixed>|list<mixed>|mixed
 */
function generate_fake_data_for_json_schema_document(array $schema, ?array $root = null): mixed
{
    $root ??= $schema;

    if (isset($schema['$ref']) && is_string($schema['$ref'])) {
        $resolved = resolve_json_schema_pointer($schema['$ref'], $root);

        if (is_array($resolved)) {
            return generate_fake_data_for_json_schema_document($resolved, $root);
        }
    }

    $type = $schema['type'] ?? null;

    if ($type === 'object' || (is_array($type) && in_array('object', $type, true))) {
        $result = [];

        foreach ($schema['properties'] ?? [] as $key => $prop) {
            if (is_array($prop)) {
                $result[$key] = generate_fake_data_for_json_schema_document($prop, $root);
            }
        }

        return $result;
    }

    if ($type === 'array' || (is_array($type) && in_array('array', $type, true))) {
        $items = $schema['items'] ?? null;

        if (! is_array($items)) {
            return [];
        }

        return [generate_fake_data_for_json_schema_document($items, $root)];
    }

    if ($type === 'string' || (is_array($type) && in_array('string', $type, true))) {
        return 'string';
    }

    if ($type === 'integer' || (is_array($type) && in_array('integer', $type, true))) {
        return 0;
    }

    if ($type === 'number' || (is_array($type) && in_array('number', $type, true))) {
        return 0.0;
    }

    if ($type === 'boolean' || (is_array($type) && in_array('boolean', $type, true))) {
        return true;
    }

    return [];
}

/**
 * @param  array<string, mixed>  $root
 * @return array<string, mixed>|null
 */
function resolve_json_schema_pointer(string $ref, array $root): ?array
{
    if (! str_starts_with($ref, '#/')) {
        return null;
    }

    $segments = explode('/', substr($ref, 2));
    $current = $root;

    foreach ($segments as $segment) {
        if (! is_array($current) || ! array_key_exists($segment, $current)) {
            return null;
        }

        $current = $current[$segment];
    }

    return is_array($current) ? $current : null;
}
