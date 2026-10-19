<?php

namespace Tests\Feature;

use App\Models\AgrObject;
use App\Models\MobileReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileAppApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $object = AgrObject::firstOrCreate(['IMEI' => '920000000000001'], [
            'address' => 'Pärnu mnt 1',
            'City' => 'Tallinn',
            'dtype' => 1,
            'status' => 1,
        ]);

        return $overrides + [
            'house_id' => $object->id,
            'client_uuid' => 'c0ffee00-0000-4000-8000-000000000001',
            'installer' => 'Jaan Tamm',
            'device_type' => 'water_meter',
            'action_type' => 'install',
            'apartment' => '12',
            'qr_id' => '1083123',
            'dn_type' => '',
            'water_type' => '1',
        ];
    }

    public function test_report_is_saved_with_installer_and_resend_does_not_duplicate(): void
    {
        Storage::fake();

        $first = $this->post('/insert_dev_data', $this->payload())->assertOk()->json();
        $this->assertSame('ok', $first['status']);

        // Ответ потерялся, приложение шлёт тот же отчёт ещё раз
        $retry = $this->post('/insert_dev_data', $this->payload())->assertOk()->json();
        $this->assertSame($first['id'], $retry['id']);
        $this->assertTrue($retry['duplicate']);

        $this->assertSame(1, MobileReport::count());
        $report = MobileReport::first();
        $this->assertSame('Jaan Tamm', $report->installer);
        $this->assertArrayNotHasKey('installer', $report->data);
        $this->assertArrayNotHasKey('client_uuid', $report->data);
        // Не выбранный DN не показывается как «DN 15»
        $this->assertArrayNotHasKey('dn_type', $report->fields());

        $this->post('/insert_dev_data', $this->payload(['client_uuid' => 'another']))->assertOk();
        $this->assertSame(2, MobileReport::count());
    }

    public function test_validation_errors_are_readable_for_installer(): void
    {
        $response = $this->post('/insert_dev_data', $this->payload(['device_type' => '']))->assertStatus(422);

        $this->assertSame('Vali seadme tüüp', $response->json('errors.device_type.0'));
    }
}
