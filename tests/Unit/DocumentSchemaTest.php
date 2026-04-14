<?php

namespace Tests\Unit;

use Laravel\Ai\DocumentSchema;
use Tests\TestCase;

class DocumentSchemaTest extends TestCase
{
    public function test_recursive_defs_and_ref_preserve_structure_and_set_additional_properties(): void
    {
        $document = new DocumentSchema([
            'type' => 'object',
            'properties' => [
                'root' => ['$ref' => '#/$defs/Node'],
            ],
            'required' => ['root'],
            '$defs' => [
                'Node' => [
                    'type' => 'object',
                    'properties' => [
                        'label' => ['type' => 'string'],
                        'children' => [
                            'type' => 'array',
                            'items' => ['$ref' => '#/$defs/Node'],
                        ],
                    ],
                    'required' => ['label'],
                ],
            ],
        ]);

        $result = $document->toSchema();

        $this->assertArrayHasKey('$defs', $result);
        $this->assertSame('#/$defs/Node', $result['properties']['root']['$ref']);
        $this->assertFalse($result['additionalProperties']);
        $this->assertFalse($result['$defs']['Node']['additionalProperties']);
        $this->assertSame(
            '#/$defs/Node',
            $result['$defs']['Node']['properties']['children']['items']['$ref']
        );
    }

    public function test_pure_ref_property_is_unchanged(): void
    {
        $document = new DocumentSchema([
            'type' => 'object',
            'properties' => [
                'link' => ['$ref' => '#/$defs/Other'],
            ],
            '$defs' => [
                'Other' => [
                    'type' => 'object',
                    'properties' => ['id' => ['type' => 'string']],
                ],
            ],
        ]);

        $result = $document->toSchema();

        $this->assertSame(['$ref' => '#/$defs/Other'], $result['properties']['link']);
    }
}
