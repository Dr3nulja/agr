<?php

namespace Tests\Feature;

use App\Models\AgrObject;
use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression test: queueCommand() wrote the command under an 'Content'
     * (capital C) key, but the real `requests` table column is lowercase
     * `content`. MySQL accepted the mismatched INSERT column name silently,
     * but reading it back via a PHP object property is case-sensitive, so
     * getPendingCommandByImei() always returned an empty string.
     */
    public function test_queued_command_can_be_read_back_by_imei(): void
    {
        $object = AgrObject::create([
            'address' => 'Test Street 5',
            'City' => 'Tallinn',
            'IMEI' => '555555555555555',
            'dtype' => 1,
            'status' => 1,
        ]);

        $service = app(LegacyObjectsService::class);
        $service->queueCommand($object->id, 1); // 1 => restart

        $content = $service->getPendingCommandByImei($object->IMEI);

        $this->assertSame('restart', $content);
    }

    public function test_unknown_command_code_is_ignored(): void
    {
        $object = AgrObject::create([
            'address' => 'Test Street 6',
            'City' => 'Tallinn',
            'IMEI' => '666666666666666',
            'dtype' => 1,
            'status' => 1,
        ]);

        $service = app(LegacyObjectsService::class);
        $service->queueCommand($object->id, 99);

        $this->assertNull($service->getPendingCommandByImei($object->IMEI));
    }
}
