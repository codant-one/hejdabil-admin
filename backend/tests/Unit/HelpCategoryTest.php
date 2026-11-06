<?php

namespace Tests\Unit;

use App\Models\HelpCategory;
use PHPUnit\Framework\TestCase;

class HelpCategoryTest extends TestCase
{
    public function test_category_fields_can_be_mass_assigned(): void
    {
        $attributes = [
            'name' => 'Overview',
            'label' => 'summary',
            'icon' => 'help-categories/summary.svg',
        ];

        $category = new HelpCategory($attributes);

        $this->assertSame($attributes, $category->getAttributes());

        $category->fill([
            'name' => 'Customers',
            'label' => 'clients',
            'icon' => 'help-categories/clients.svg',
        ]);

        $this->assertSame('Customers', $category->name);
        $this->assertSame('clients', $category->label);
        $this->assertSame('help-categories/clients.svg', $category->icon);
        $this->assertFalse($category->isFillable('id'));
    }
}
