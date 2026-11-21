<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_schema_is_migratable_without_external_ai(): void
    {
        foreach (
            [
                'branches',
                'users',
                'orders',
                'inventories',
                'inventory_movements',
                'payrolls',
                'audit_logs',
            ]
            as $table
        ) {
            $this->assertTrue(Schema::hasTable($table));
        }
    }

    public function test_public_registration_is_not_exposed(): void
    {
        $this->post('/register', ['email' => 'unknown@example.test'])->assertNotFound();
    }
}
