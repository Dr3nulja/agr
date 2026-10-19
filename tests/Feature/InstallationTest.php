<?php

namespace Tests\Feature;

use App\Models\AgrObject;
use App\Models\MobileReport;
use App\Models\ObjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstallationTest extends TestCase
{
    use RefreshDatabase;

    private AgrObject $object;
    private array $session;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();

        $user = User::create(['name' => 'Admin', 'login' => 'admin', 'pass' => 'secret', 'role' => 1]);
        $this->session = ['user_id' => $user->id, 'user' => $user];

        $this->object = AgrObject::create([
            'address' => 'Narva mnt 55',
            'City' => 'Tallinn',
            'IMEI' => '910000000000001',
            'dtype' => 1,
            'status' => 1,
        ]);
        ObjectTask::create(['object_id' => $this->object->id, 'device_type' => 'water_meter', 'action_type' => 'replace']);

        // Список квартир объекта (легаси flat_N, создаётся хранимой процедурой на проде)
        DB::statement("CREATE TABLE flat_{$this->object->id} (id int NOT NULL AUTO_INCREMENT, location varchar(5) NOT NULL, size float NOT NULL, PRIMARY KEY (id))");
        foreach (['1', '2', '10', '11'] as $flat) {
            DB::table('flat_' . $this->object->id)->insert(['location' => $flat, 'size' => 50]);
        }
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('flat_' . $this->object->id);
        parent::tearDown();
    }

    private function report(string $apartment, string $device = 'water_meter', array $data = [], bool $photo = false): MobileReport
    {
        $report = MobileReport::create([
            'object_id' => $this->object->id,
            'device_type' => $device,
            'action_type' => 'replace',
            'apartment' => $apartment,
            'qr_id' => 'QR-' . $apartment,
            'last_reading' => '123',
            'data' => $data + ['dn_type' => '15', 'water_type' => '2', 'kitchen_floor' => '1', 'kitchen_ceiling' => '0', 'qr_id_comment' => 'kleebis kulunud'],
        ]);

        if ($photo) {
            $image = imagecreatetruecolor(60, 80);
            ob_start();
            imagejpeg($image);
            Storage::put('mobile_reports/' . $report->id . '/photo_after.jpg', ob_get_clean());
            $report->update(['photo_after' => 'mobile_reports/' . $report->id . '/photo_after.jpg']);
        }

        return $report;
    }

    public function test_report_fields_have_labels_and_skip_other_device_fields(): void
    {
        $water = $this->report('1');
        $allocator = $this->report('2', 'allocator', ['radiator_type' => 'Malm']);

        $fields = collect($water->fields())->map(fn ($field) => $field[1])->all();
        $this->assertSame('Soe vesi', $fields['water_type']);
        $this->assertSame('DN 15', $fields['dn_type']);
        $this->assertSame('Jah', $fields['kitchen_floor']);
        $this->assertArrayNotHasKey('kitchen_ceiling', $fields);
        $this->assertSame('kleebis kulunud', $fields['qr_id_comment']);

        $allocatorFields = $allocator->fields();
        $this->assertSame(['Radiaatori tüüp', 'Malm'], $allocatorFields['radiator_type']);
        $this->assertArrayNotHasKey('dn_type', $allocatorFields);
        $this->assertArrayNotHasKey('last_reading', $allocatorFields);
    }

    public function test_index_shows_progress_against_flat_list(): void
    {
        $this->report('1');
        $this->report('1');
        $this->report('10');

        $this->withSession($this->session)->get('/installations')
            ->assertOk()
            ->assertSee('Narva mnt 55')
            ->assertSee('2 / 4 krt · 50%');
    }

    public function test_show_groups_by_apartment_and_lists_missing_flats(): void
    {
        $this->report('10');
        $this->report('2');
        $this->report('11', 'allocator');

        $response = $this->withSession($this->session)->get('/installations/' . $this->object->id)->assertOk();
        $response->assertSeeInOrder(['Krt 2', 'Krt 10', 'Krt 11']);
        $response->assertSee('Aruandeta korterid (1)');

        $this->withSession($this->session)->get('/installations/' . $this->object->id . '?device=allocator')
            ->assertOk()
            ->assertSee('Krt 11')
            ->assertDontSee('Krt 10');
    }

    public function test_print_and_excel_exports(): void
    {
        $this->report('1', photo: true);
        $this->report('2', 'allocator', ['radiator_type' => 'Malm']);

        $this->withSession($this->session)->get('/installations/' . $this->object->id . '/print')
            ->assertOk()
            ->assertSee('Paigaldusaruanne')
            ->assertSee('Korter 1')
            ->assertSee('Malm');

        foreach (['', '?photos=1'] as $query) {
            $response = $this->withSession($this->session)->get('/installations/' . $this->object->id . '/export.xlsx' . $query);
            $response->assertOk();
            $file = tempnam(sys_get_temp_dir(), 'xlsx');
            file_put_contents($file, $response->streamedContent());

            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getActiveSheet();
            $this->assertSame('Korter', $sheet->getCell('A4')->getValue());
            $this->assertSame('1', $sheet->getCell('A5')->getValue());
            $this->assertSame('QR-1', $sheet->getCell('D5')->getValue());
            $this->assertCount($query ? 1 : 0, $sheet->getDrawingCollection());
            unlink($file);
        }
    }

    public function test_installation_routes_redirect_guests_to_login(): void
    {
        foreach (['/installations', '/installations/1', '/installations/1/print', '/installations/1/export.xlsx'] as $route) {
            $this->get($route)->assertRedirect('/login');
        }
    }
}
