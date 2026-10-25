<?php

namespace Tests\Unit;

use App\Models\SupplierActivity;
use App\Services\ActivityMetadataResolver;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ActivityMetadataResolverTest extends TestCase
{
    private function resolver(): ActivityMetadataResolver
    {
        $resolver = new ActivityMetadataResolver();
        $catalog = new ReflectionProperty($resolver, 'invoiceTypes');
        $catalog->setValue($resolver, new Collection([
            (object) ['id' => 1, 'name' => 'Inkomst'],
            (object) ['id' => 2, 'name' => 'Utgift'],
        ]));

        return $resolver;
    }

    public function test_invoice_type_ids_are_resolved_in_old_and_new_values(): void
    {
        $activity = new SupplierActivity();
        $activity->setAttribute('metadata', json_encode([
            'old_values' => ['type_id' => 1],
            'new_values' => ['type_id' => '2', 'name' => 'Invoice'],
        ]));

        $resolved = $this->resolver()->enrichCollection(new Collection([$activity]));

        $this->assertSame($activity, $resolved->first());
        $this->assertSame([
            'old_values' => ['type_id' => 'Inkomst'],
            'new_values' => ['type_id' => 'Utgift', 'name' => 'Invoice'],
        ], $activity->metadata);
    }

    public function test_empty_unknown_and_already_resolved_types_are_preserved(): void
    {
        $resolver = $this->resolver();

        foreach ([null, '', 'null', 'undefined', 999, 'Inkomst'] as $value) {
            $activity = new SupplierActivity();
            $activity->setAttribute('metadata', [
                'new_values' => ['type_id' => $value],
            ]);

            $resolver->enrichCollection(new Collection([$activity]));

            $this->assertSame($value, $activity->metadata['new_values']['type_id']);
        }
    }
}
