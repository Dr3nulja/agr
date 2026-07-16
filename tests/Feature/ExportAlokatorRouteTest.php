<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExportAlokatorRouteTest extends TestCase
{
    public function test_alokator_export_route_is_protected_and_available(): void
    {
        $response = $this->get('/objects/1/export-alokator');

        $response->assertRedirect('/login');
    }
}
