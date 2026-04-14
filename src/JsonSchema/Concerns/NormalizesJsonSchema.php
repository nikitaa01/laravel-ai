<?php

namespace Laravel\Ai\JsonSchema\Concerns;

trait NormalizesJsonSchema
{
    /**
     * Recursively set "additionalProperties" to false on all object nodes, including under $defs/definitions.
     * Pure $ref leaf nodes are left unchanged.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    protected static function disableAdditionalProperties(array $schema): array
    {
        if (static::isRefLeaf($schema)) {
            return $schema;
        }

        foreach (['$defs', 'definitions'] as $defsKey) {
            if (! isset($schema[$defsKey]) || ! is_array($schema[$defsKey])) {
                continue;
            }

            foreach ($schema[$defsKey] as $defName => $defSchema) {
                if (is_array($defSchema)) {
                    $schema[$defsKey][$defName] = static::disableAdditionalProperties($defSchema);
                }
            }
        }

        foreach (['oneOf', 'anyOf', 'allOf'] as $combiner) {
            if (! isset($schema[$combiner]) || ! is_array($schema[$combiner])) {
                continue;
            }

            foreach ($schema[$combiner] as $i => $subSchema) {
                if (is_array($subSchema)) {
                    $schema[$combiner][$i] = static::disableAdditionalProperties($subSchema);
                }
            }
        }

        $type = $schema['type'] ?? null;

        if ($type === 'object' || (is_array($type) && in_array('object', $type, true))) {
            $schema['additionalProperties'] = false;

            foreach ($schema['properties'] ?? [] as $key => $property) {
                if (is_array($property)) {
                    $schema['properties'][$key] = static::disableAdditionalProperties($property);
                }
            }
        }

        if (is_array($schema['items'] ?? null)) {
            $items = $schema['items'];

            if (array_is_list($items)) {
                foreach ($items as $i => $itemSchema) {
                    if (is_array($itemSchema)) {
                        $schema['items'][$i] = static::disableAdditionalProperties($itemSchema);
                    }
                }
            } else {
                $schema['items'] = static::disableAdditionalProperties($items);
            }
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    protected static function isRefLeaf(array $schema): bool
    {
        if (! array_key_exists('$ref', $schema)) {
            return false;
        }

        $allowedSiblingKeys = ['$ref', 'description', 'title', 'default'];

        foreach (array_keys($schema) as $key) {
            if (! in_array($key, $allowedSiblingKeys, true)) {
                return false;
            }
        }

        return true;
    }
}
