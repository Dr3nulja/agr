<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExportAlokatorRouteTest extends TestCase
{
    public function test_export_routes_are_protected_and_available(): void
    {
        $this->get('/objects/1/export-alokator')
            ->assertRedirect('/login');

        $this->get('/objects/1/export-korto')
            ->assertRedirect('/login');

        $this->get('/objects/1/export-month-start')
            ->assertRedirect('/login');
    }
}
