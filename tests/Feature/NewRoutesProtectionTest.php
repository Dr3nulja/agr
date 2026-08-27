<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Smoke test for every route added while restoring feature parity with the
 * legacy PHP app (SOE cost reports, CSV imports, radiator upload, send
 * command, device upload): guests must be bounced to login, not 404 or 500.
 */
class NewRoutesProtectionTest extends TestCase
{
    public function test_new_get_routes_redirect_guests_to_login(): void
    {
        $routes = [
            '/objects/1/devices/upload',
            '/objects/1/import-csv',
            '/objects/1/soe/report.csv?maks=100&kuu=1',
            '/objects/1/soe/report.xlsx?maks=100&kuu=1',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertRedirect('/login');
        }
    }

    public function test_new_post_routes_redirect_guests_to_login(): void
    {
        $routes = [
            '/objects/1/devices/upload',
            '/objects/1/import-csv/apator-water',
            '/objects/1/import-csv/apator-heater',
            '/objects/1/import-csv/siemens',
            '/objects/1/soe/radiators/upload',
            '/objects/1/command',
        ];

        foreach ($routes as $route) {
            $this->post($route)->assertRedirect('/login');
        }
    }
}
