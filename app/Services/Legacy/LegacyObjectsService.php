<?php

namespace App\Services\Legacy;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyObjectsService
{
    public function getFlats(int $objectId): array
    {
        if (! Schema::hasTable('flat_' . $objectId)) {
            return [];
        }

        return DB::table('flat_' . $objectId)
            ->select(['id', 'location', 'size'])
            ->orderBy('id')
            ->get()
            ->map(function ($row): array {
                return [
                    'id' => $row->id,
                    'location' => $row->location,
                    'size' => $row->size,
                ];
            })
            ->all();
    }

    public function addFlat(int $objectId, string $location, float $size): void
    {
        DB::table('flat_' . $objectId)->insert([
            'location' => $location,
            'size' => $size,
        ]);
    }

    public function updateFlat(int $objectId, int $flatId, string $location, float $size): void
    {
        DB::table('flat_' . $objectId)
            ->where('id', $flatId)
            ->update([
                'location' => $location,
                'size' => $size,
            ]);
    }

    public function deleteFlat(int $objectId, int $flatId): void
    {
        DB::table('flat_' . $objectId)
            ->where('id', $flatId)
            ->delete();
    }

    public function getRadiators(int $objectId, string $flat = ''): array
    {
        if (! Schema::hasTable('object_' . $objectId) || ! Schema::hasTable('heat_' . $objectId)) {
            return [];
        }

        return DB::table('object_' . $objectId . ' as O')
            ->leftJoin('heat_' . $objectId . ' as H', 'O.devid', '=', 'H.devid')
            ->select(['O.id as object_row_id', 'O.location', 'O.devid', 'H.power', 'H.cof', 'H.size', 'H.description'])
            ->where(function ($query): void {
                $query->where('O.devtype', 3)->orWhereBetween('O.devtype', [30, 39]);
            })
            ->when($flat !== '', function ($query) use ($flat): void {
                $query->where('O.location', $flat);
            })
            ->orderBy('O.id')
            ->get()
            ->map(function ($row): array {
                return [
                    'object_row_id' => $row->object_row_id,
                    'location' => $row->location,
                    'devid' => $row->devid,
                    'power' => $row->power,
                    'cof' => $row->cof,
                    'size' => $row->size,
                    'description' => $row->description,
                ];
            })
            ->all();
    }

    public function getFlatSummary(int $objectId): array
    {
        if (! Schema::hasTable('flat_' . $objectId)) {
            return [
                'flat_count' => 0,
                'total_area' => 0.0,
            ];
        }

        $summary = DB::table('flat_' . $objectId)
            ->selectRaw('COUNT(*) as flat_count, COALESCE(SUM(size), 0) as total_area')
            ->first();

        return [
            'flat_count' => (int) ($summary->flat_count ?? 0),
            'total_area' => (float) ($summary->total_area ?? 0),
        ];
    }

    public function getFlatRadiatorSummary(int $objectId, string $flat = ''): array
    {
        if (! Schema::hasTable('object_' . $objectId) || ! Schema::hasTable('heat_' . $objectId)) {
            return [];
        }

        $query = DB::table('object_' . $objectId . ' as O')
            ->leftJoin('heat_' . $objectId . ' as H', 'O.devid', '=', 'H.devid')
            ->selectRaw('O.location as flat_name, COUNT(*) as radiator_count, COALESCE(SUM(H.power), 0) as total_power, COALESCE(SUM(H.size), 0) as total_size')
            ->where(function ($builder): void {
                $builder->where('O.devtype', 3)->orWhereBetween('O.devtype', [30, 39]);
            });

        if ($flat !== '') {
            $query->where('O.location', $flat);
        }

        return $query
            ->groupBy('O.location')
            ->orderBy('O.location')
            ->get()
            ->map(function ($row): array {
                return [
                    'flat_name' => $row->flat_name,
                    'radiator_count' => (int) $row->radiator_count,
                    'total_power' => (float) $row->total_power,
                    'total_size' => (float) $row->total_size,
                ];
            })
            ->all();
    }

    public function replaceFlatList(int $objectId, string $filePath): void
    {
        if (! Schema::hasTable('flat_' . $objectId)) {
            return;
        }

        $this->dropFlatListData($objectId);

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return;
        }

        $rowIndex = 0;

        while (($line = fgets($handle)) !== false) {
            $rowIndex++;
            $fields = array_map('trim', explode(';', $line));

            if ($rowIndex <= 1) {
                continue;
            }

            if (count($fields) >= 2 && $fields[0] !== '') {
                $pind = $fields[1];

                if (str_contains($pind, '.')) {
                    $pind = str_replace('.', ',', $pind);
                }

                $this->insertFlatListRow($objectId, $fields[0], $pind);
            }
        }

        fclose($handle);
    }

    public function replaceRadiatorList(int $objectId, string $filePath): void
    {
        if (! Schema::hasTable('heat_' . $objectId)) {
            return;
        }

        DB::statement('TRUNCATE TABLE heat_' . $objectId);

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return;
        }

        $rowIndex = 0;

        while (($line = fgets($handle)) !== false) {
            $rowIndex++;
            $fields = array_map('trim', explode(';', $line));

            // Legacy skips the first 4 header/meta lines of the export.
            if ($rowIndex <= 4) {
                continue;
            }

            if (count($fields) >= 6 && $fields[2] !== '') {
                DB::table('heat_' . $objectId)->insertOrIgnore([
                    'devid' => $fields[2],
                    'power' => $fields[3],
                    'cof' => $fields[4],
                    'size' => $fields[5],
                    'description' => '',
                ]);
            }
        }

        fclose($handle);
    }

    public function replaceDeviceList(int $objectId, string $filePath): void
    {
        if (! Schema::hasTable('object_' . $objectId)) {
            return;
        }

        $this->dropDeviceListData($objectId);

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return;
        }

        while (($line = fgets($handle)) !== false) {
            $fields = array_map('trim', explode(';', $line));

            if (count($fields) === 4 && $fields[0] !== '') {
                $this->insertDeviceListRow($objectId, (int) $fields[0], (int) $fields[1], $fields[2], (int) $fields[3]);
            }
        }

        fclose($handle);
    }

    public function getLegacyDeviceIds(string $imei): array
    {
        $objectId = $this->findObjectIdByImei($imei);

        if ($objectId === null) {
            return [];
        }

        return DB::table('object_' . $objectId)
            ->select(['devid'])
            ->orderBy('devid')
            ->get()
            ->map(function ($row): string {
                return base64_encode(pack('V', (int) $row->devid));
            })
            ->all();
    }

    public function changeDeviceId(int $objectId, int $previousId, int $newId): void
    {
        DB::table('object_' . $objectId)
            ->where('devid', $previousId)
            ->update(['devid' => $newId]);
    }

    public function getSoeSettings(int $objectId): array
    {
        $record = DB::table('objects')
            ->select(['m2_andur', 'dataToPage', 'AddFee', 'fee', 'kuluM2', 'AlgLopp', 'saveHval'])
            ->where('id', $objectId)
            ->first();

        if ($record === null) {
            return [
                'm2_source' => 50,
                'kuludm2' => 0,
                'lisamaks' => 0,
                'lisamaksen' => false,
                'eraldileht' => false,
                'ParamKulu' => false,
                'AlgLopp' => false,
            ];
        }

        return [
            'm2_source' => (int) $record->m2_andur,
            'kuludm2' => (int) $record->kuluM2,
            'lisamaks' => (float) $record->fee,
            'lisamaksen' => (int) $record->AddFee === 1,
            'eraldileht' => (int) $record->dataToPage === 1,
            'ParamKulu' => (int) $record->kuluM2 > 0,
            'AlgLopp' => (int) $record->AlgLopp === 1,
        ];
    }

    public function saveSoeSettings(int $objectId, array $data): void
    {
        DB::table('objects')
            ->where('id', $objectId)
            ->update([
                'm2_andur' => (int) $data['m2_source'],
                'dataToPage' => ! empty($data['eraldileht']) ? 1 : 0,
                'AddFee' => ! empty($data['lisamaksen']) ? 1 : 0,
                'fee' => (float) ($data['lisamaks'] ?? 0),
                'kuluM2' => (int) $data['kuludm2'],
                'AlgLopp' => ! empty($data['AlgLopp']) ? 1 : 0,
            ]);
    }

    public function storeLoraCurrentValue(int $objectTableId, string $deviceName, string $dateTime, int $value): void
    {
        DB::table('lastdata_' . $objectTableId)
            ->updateOrInsert(
                ['devid' => $deviceName],
                [
                    'date' => $dateTime,
                    'value' => $value,
                    'inserterd' => now(),
                ]
            );

        DB::table('mlog_' . $objectTableId)->insert([
            'devid' => $deviceName,
            'date' => $dateTime,
            'value' => $value,
        ]);
    }

    public function storeLoraMonthlyValue(int $objectTableId, string $deviceName, string $date, int $value): void
    {
        DB::table('lastdata_' . $objectTableId)
            ->where('devid', $deviceName)
            ->update([
                'statDate' => $date,
                'mvalue' => $value,
            ]);
    }

    public function getHistoryBetweenDates(int $objectId, string $from, string $to): array
    {
        $table = 'mlog_' . $objectId;

        return DB::table($table)
            ->select(['devid', 'date', 'value'])
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('date')
            ->get()
            ->map(function ($row): array {
                return [
                    'devid' => $row->devid,
                    'date' => $row->date,
                    'value' => $row->value,
                ];
            })
            ->all();
    }

    public function decodeLegacyImei(string $encodedImei): ?string
    {
        if ($encodedImei === '') {
            return null;
        }

        $decoded = base64_decode($encodedImei, true);

        if ($decoded === false || strlen($decoded) === 0) {
            return null;
        }

        return (string) unpack('q', $decoded)[1];
    }

    public function updateLastSessionTime(string $imei, string $ver = '', int $csq = 0): void
    {
        $sql = [];

        if ($ver !== '') {
            $sql['ver'] = $ver;
        }

        if ($csq !== 0 && $csq < 35) {
            $sql['csq'] = $csq;

            $objectId = DB::table('objects')
                ->select('id')
                ->where('IMEI', $imei)
                ->orWhere('IMEI2', $imei)
                ->value('id');

            if ($objectId !== null) {
                DB::table('csq')->insert([
                    'object' => $objectId,
                    'date' => now(),
                    'csq' => $csq,
                ]);
            }
        }

        DB::table('objects')
            ->where('IMEI', $imei)
            ->update(array_merge(['lastSession' => now()], $sql));
    }

    public function ingestLegacyRadioPacket(string $imei, string $payload): int
    {
        $objectId = $this->findObjectIdByImei($imei);

        if ($objectId === null) {
            return 0;
        }

        $this->updateLastSessionTime($imei);

        $rows = 0;

        while ($payload !== '') {
            if (strlen($payload) < 16) {
                break;
            }

            $deviceId = hexdec(bin2hex(substr($payload, 3, 1) . substr($payload, 2, 1) . substr($payload, 1, 1) . substr($payload, 0, 1)));

            $valueHex = bin2hex(substr($payload, 4, 4));
            $valueHex = substr($valueHex, 6, 2) . substr($valueHex, 4, 2) . substr($valueHex, 2, 2) . substr($valueHex, 0, 2);
            $value = $this->toSignedInt32(hexdec($valueHex));

            $dateBlock = bin2hex(substr($payload, 8, 4));
            [$year, $month, $day, $hours, $minutes] = $this->decodeLegacyDateBlock($dateBlock);
            $dateTime = date('Y-m-d H:i:s', mktime($hours, $minutes, 0, $month, $day, $year));

            $value2Hex = bin2hex(substr($payload, 12, 4));
            $value2Hex = substr($value2Hex, 6, 2) . substr($value2Hex, 4, 2) . substr($value2Hex, 2, 2) . substr($value2Hex, 0, 2);
            $mvalue = $this->toSignedInt32(hexdec($value2Hex));

            $payload = substr($payload, 16);

            if ($year <= 2010) {
                continue;
            }

            $this->storeLegacyMeasurement($objectId, $deviceId, $dateTime, $value, $mvalue);
            $rows++;
        }

        return $rows;
    }

    /**
     * Full code => device command-string map, matches addnew.class.php::addRequest.
     */
    private function allCommands(): array
    {
        return [
            1 => 'restart',
            2 => 'clearRam',
            3 => 'SendData',
            4 => 'setSearch',
            5 => 'setSearch2',
        ];
    }

    /**
     * Which commands are offered per system type - Siemens exposes a
     * different subset than Apator/Elekter/LoRa (command_si.tpl vs command_apa.tpl).
     */
    public function getCommandOptions(int $dtype = 1): array
    {
        if ($dtype === 2) {
            return [
                1 => 'Restart',
                3 => 'Send data from memory',
                4 => 'Send Search',
                5 => 'Send ExtLong search',
            ];
        }

        return [
            1 => 'Restart',
            2 => 'Reload devices list',
            3 => 'Send data from memory',
        ];
    }

    public function queueCommand(int $objectId, int $commandType): void
    {
        $commands = $this->allCommands();

        if (! isset($commands[$commandType])) {
            return;
        }

        DB::table('requests')->insert([
            'object' => $objectId,
            'content' => $commands[$commandType],
            'date' => now(),
            'handled' => 0,
        ]);
    }

    public function saveNewObject(array $data): int
    {
        $objectId = (int) DB::table('objects')->insertGetId([
            'address' => $data['address'],
            'City' => $data['city'] ?? '',
            'GSMNR' => $data['gsmnr'] ?? '',
            'IMEI' => $data['imei'],
            'Contact' => $data['contact'] ?? '',
            'Description' => $data['description'] ?? '',
            'Description2' => $data['description2'] ?? '',
            'Company' => $data['customRadio'] ?? 1,
            'dtype' => $data['customRadio2'] ?? 1,
            'status' => 1,
            'Devqtty' => $data['devq'] ?? 0,
            'RadioDevQty' => $data['radiodevq'] ?? 0,
            'MainRadio' => $data['mainradio'] ?? '',
            'GSMSERIAL' => $data['gsmserial'] ?? '',
            'pin1' => $data['pin1'] ?? '',
            'pin2' => $data['pin2'] ?? '',
            'puk1' => $data['puk1'] ?? '',
            'puk2' => $data['puk2'] ?? '',
            'KeyCode' => $data['keycode'] ?? '',
            'manager' => $data['customRadio3'] ?? 0,
            'packet' => $data['paket'] ?? '',
            'traffic' => $data['traf'] ?? '',
            'callCnt' => $data['call'] ?? '',
            'summ' => $data['summ'] ?? '',
            'IMEI2' => $data['imei2'] ?? '',
            'GSMNR2' => $data['gsmnr2'] ?? '',
            'GSMSERIAL2' => $data['gsmserial2'] ?? '',
            'lat' => $data['lat'] ?? 0,
            'lon' => $data['lon'] ?? 0,
            'saveHval' => isset($data['saveMVal1']) ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $objectId;
    }

    public function updateObject(int $objectId, array $data): void
    {
        DB::table('objects')
            ->where('id', $objectId)
            ->update([
                'address' => $data['address'],
                'City' => $data['city'] ?? '',
                'GSMNR' => $data['gsmnr'] ?? '',
                'IMEI' => $data['imei'],
                'Contact' => $data['contact'] ?? '',
                'Description' => $data['description'] ?? '',
                'Description2' => $data['description2'] ?? '',
                'Company' => $data['customRadio'] ?? 1,
                'dtype' => $data['customRadio2'] ?? 1,
                'status' => 1,
                'Devqtty' => $data['devq'] ?? 0,
                'RadioDevQty' => $data['radiodevq'] ?? 0,
                'MainRadio' => $data['mainradio'] ?? '',
                'GSMSERIAL' => $data['gsmserial'] ?? '',
                'pin1' => $data['pin1'] ?? '',
                'pin2' => $data['pin2'] ?? '',
                'puk1' => $data['puk1'] ?? '',
                'puk2' => $data['puk2'] ?? '',
                'KeyCode' => $data['keycode'] ?? '',
                'manager' => $data['customRadio3'] ?? 0,
                'packet' => $data['paket'] ?? '',
                'traffic' => $data['traf'] ?? '',
                'callCnt' => $data['call'] ?? '',
                'summ' => $data['summ'] ?? '',
                'IMEI2' => $data['imei2'] ?? '',
                'GSMNR2' => $data['gsmnr2'] ?? '',
                'GSMSERIAL2' => $data['gsmserial2'] ?? '',
                'lat' => $data['lat'] ?? 0,
                'lon' => $data['lon'] ?? 0,
                'saveHval' => isset($data['saveMVal1']) ? 1 : 0,
                'updated_at' => now(),
            ]);
    }

    public function quickUpdateObject(int $objectId, array $data): void
    {
        DB::table('objects')
            ->where('id', $objectId)
            ->update([
                'address' => $data['address'],
                'City' => $data['city'] ?? '',
                'Contact' => $data['contact'] ?? '',
                'IMEI' => $data['imei'],
                'GSMNR' => $data['gsmnr'] ?? '',
                'Devqtty' => $data['devq'] ?? 0,
                'RadioDevQty' => $data['radiodevq'] ?? 0,
                'MainRadio' => $data['mainradio'] ?? '',
                'GSMSERIAL' => $data['gsmserial'] ?? '',
                'Description' => $data['description'] ?? '',
                'lat' => $data['lat'] ?? 0,
                'lon' => $data['lon'] ?? 0,
                'updated_at' => now(),
            ]);
    }

    public function updateDeviceRow(int $objectId, int $rowId, int $deviceId, string $location, int $deviceType): void
    {
        DB::table('object_' . $objectId)
            ->where('id', $rowId)
            ->update([
                'devid' => $deviceId,
                'location' => $location,
                'devtype' => $deviceType,
            ]);
    }

    public function addDeviceRow(int $objectId, int $deviceId, string $location, int $deviceType): void
    {
        $nextId = (int) (DB::table('object_' . $objectId)->max('id') ?? 0) + 1;

        DB::table('object_' . $objectId)->insert([
            'id' => $nextId,
            'devid' => $deviceId,
            'location' => $location,
            'devtype' => $deviceType,
        ]);
    }

    public function deleteDeviceRow(int $objectId, int $rowId): void
    {
        DB::table('object_' . $objectId)
            ->where('id', $rowId)
            ->delete();
    }

    public function getObjectDetails(int $objectId): ?array
    {
        $record = DB::table('objects')
            ->select(['*'])
            ->where('id', $objectId)
            ->first();

        if ($record === null) {
            return null;
        }

        return [
            'id' => $record->id,
            'address' => $this->getNullableRecordValue($record, 'address'),
            'city' => $this->getNullableRecordValue($record, 'City'),
            'contact' => $this->getNullableRecordValue($record, 'Contact'),
            'imei' => $this->getNullableRecordValue($record, 'IMEI'),
            'gsmnr' => $this->getNullableRecordValue($record, 'GSMNR'),
            'description' => $this->getNullableRecordValue($record, 'Description'),
            'description2' => $this->getNullableRecordValue($record, 'Description2'),
            'installer' => (int) $this->getNullableRecordValue($record, 'Company', 0),
            'system' => (int) $this->getNullableRecordValue($record, 'dtype', 0),
            'devq' => $this->getNullableRecordValue($record, 'Devqtty'),
            'radiodevq' => $this->getNullableRecordValue($record, 'RadioDevQty'),
            'mainradio' => $this->getNullableRecordValue($record, 'MainRadio'),
            'gsmserial' => $this->getNullableRecordValue($record, 'GSMSERIAL'),
            'pin1' => $this->getNullableRecordValue($record, 'pin1'),
            'pin2' => $this->getNullableRecordValue($record, 'pin2'),
            'puk1' => $this->getNullableRecordValue($record, 'puk1'),
            'puk2' => $this->getNullableRecordValue($record, 'puk2'),
            'packet' => $this->getNullableRecordValue($record, 'packet'),
            'traffic' => $this->getNullableRecordValue($record, 'traffic'),
            'call' => $this->getNullableRecordValue($record, 'callCnt'),
            'summ' => $this->getNullableRecordValue($record, 'summ'),
            'keycode' => $this->getNullableRecordValue($record, 'KeyCode'),
            'manager' => (int) $this->getNullableRecordValue($record, 'manager', 0),
            'imei2' => $this->getNullableRecordValue($record, 'IMEI2'),
            'gsmnr2' => $this->getNullableRecordValue($record, 'GSMNR2'),
            'gsmserial2' => $this->getNullableRecordValue($record, 'GSMSERIAL2'),
            'lat' => $this->getNullableRecordValue($record, 'lat'),
            'lon' => $this->getNullableRecordValue($record, 'lon'),
            'save_month_value' => (int) ($this->getNullableRecordValue($record, 'saveHval', 0) === 1),
            'm2_andur' => (int) $this->getNullableRecordValue($record, 'm2_andur', 0),
            'dataToPage' => (int) $this->getNullableRecordValue($record, 'dataToPage', 0),
            'AddFee' => (int) $this->getNullableRecordValue($record, 'AddFee', 0),
            'fee' => $this->getNullableRecordValue($record, 'fee', 0),
            'kuluM2' => (int) $this->getNullableRecordValue($record, 'kuluM2', 0),
            'AlgLopp' => (int) $this->getNullableRecordValue($record, 'AlgLopp', 0),
        ];
    }

    private function getNullableRecordValue(object $record, string $field, mixed $default = null): mixed
    {
        return property_exists($record, $field) ? $record->$field : $default;
    }

    public function getPendingCommandByImei(string $imei): ?string
    {
        $object = DB::table('objects')
            ->select(['id'])
            ->where(function ($query) use ($imei): void {
                $query->where('IMEI', $imei)
                    ->orWhere('IMEI2', $imei);
            })
            ->first();

        if ($object === null) {
            return null;
        }

        $request = DB::table('requests')
            ->where('object', $object->id)
            ->where('handled', 0)
            ->orderBy('date')
            ->first();

        if ($request === null) {
            return null;
        }

        DB::table('requests')
            ->where('id', $request->id)
            ->update([
                'date' => now(),
                'handled' => 1,
            ]);

        return (string) $request->content;
    }

    private function findObjectIdByImei(string $imei): ?int
    {
        $objectId = DB::table('objects')
            ->where(function ($query) use ($imei): void {
                $query->where('IMEI', $imei)
                    ->orWhere('IMEI2', $imei);
            })
            ->value('id');

        return $objectId === null ? null : (int) $objectId;
    }

    private function dropDeviceListData(int $objectId): void
    {
        DB::statement('TRUNCATE TABLE object_' . $objectId);
    }

    private function insertDeviceListRow(int $objectId, int $id, int $devid, string $location, int $type): void
    {
        DB::table('object_' . $objectId)->insert([
            'id' => $id,
            'devid' => $devid,
            'location' => $location,
            'devtype' => $type,
        ]);
    }

    private function dropFlatListData(int $objectId): void
    {
        DB::statement('TRUNCATE TABLE flat_' . $objectId);
    }

    private function insertFlatListRow(int $objectId, string $location, string $pind): void
    {
        DB::table('flat_' . $objectId)->insert([
            'location' => $location,
            'size' => $pind,
        ]);
    }

    private function storeLegacyMeasurement(int $objectId, int $deviceId, string $dateTime, int $value, int $mvalue): void
    {
        $logTable = 'mlog_' . $objectId;
        $lastTable = 'lastdata_' . $objectId;

        DB::table($logTable)->insert([
            'devid' => $deviceId,
            'date' => $dateTime,
            'value' => $value,
        ]);

        DB::table($lastTable)->updateOrInsert(
            ['devid' => $deviceId],
            [
                'date' => $dateTime,
                'value' => $value,
                'mvalue' => $mvalue,
                'inserterd' => now(),
            ]
        );
    }

    private function decodeLegacyDateBlock(string $dateBlock): array
    {
        if (strlen($dateBlock) < 8) {
            return [2010, 1, 1, 0, 0];
        }

        if (substr($dateBlock, 4, 4) !== '0000') {
            $year = (hexdec(substr($dateBlock, 6, 2)) & 127) + 2000;
            $month = hexdec(substr($dateBlock, 4, 2)) & 15;
            $day = hexdec(substr($dateBlock, 2, 2)) & 31;
            $hours = (((hexdec(substr($dateBlock, 0, 2)) >> 6) & 3) | ((hexdec(substr($dateBlock, 2, 2)) >> 3) & 28));
            $minutes = hexdec(substr($dateBlock, 0, 2)) & 63;

            return [$year, $month, $day, $hours, $minutes];
        }

        $hours = 0;
        $minutes = 0;
        $year = ((hexdec(substr($dateBlock, 2, 2)) & 254) >> 1) + 2000;
        $month = (((hexdec(substr($dateBlock, 2, 2)) & 1) << 3) | ((hexdec(substr($dateBlock, 0, 2)) & 224) >> 5));
        $day = hexdec(substr($dateBlock, 0, 2)) & 31;

        return [$year, $month, $day, $hours, $minutes];
    }

    private function toSignedInt32(int $value): int
    {
        return $value >= 2147483648 ? $value - 4294967296 : $value;
    }

    /**
     * Import a legacy Apator "water" meter-reading CSV export. Every row shares
     * the single date/time the installer captured on-site (importApatorWater.class.php).
     */
    public function importApatorWaterCsv(int $objectId, string $filePath, string $dateTime): int
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return 0;
        }

        $firstRow = true;
        $imported = 0;

        while (($line = fgets($handle)) !== false) {
            $fields = explode(';', $line);

            if (! $firstRow && count($fields) > 10) {
                $devid = (int) $fields[0];
                $curVal = (float) str_replace(',', '.', $fields[13]);
                $prevVal = (float) str_replace(',', '.', $fields[14]);
                $prevVal2 = (float) str_replace(',', '.', $fields[15]);

                $this->importRadioReading($objectId, $devid, $dateTime, $curVal, $prevVal, $prevVal2);
                $imported++;
            }

            $firstRow = false;
        }

        fclose($handle);

        return $imported;
    }

    /**
     * Import a legacy Apator heat-allocator CSV export. The source file repeats
     * each reading across 4 rows; only the 2nd of every group carries the data
     * we want (importApatorHeater.class.php).
     */
    public function importApatorHeaterCsv(int $objectId, string $filePath): int
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return 0;
        }

        $firstRow = true;
        $cycle = 1;
        $imported = 0;

        while (($line = fgets($handle)) !== false) {
            $fields = explode(';', $line);

            if (! $firstRow && count($fields) === 15) {
                if ($cycle === 2) {
                    $devid = (int) str_replace('"', '', $fields[0]);
                    $rawDate = str_replace('"', '', $fields[7]);
                    $curVal = (float) str_replace(',', '.', str_replace('"', '', $fields[8]));
                    $prevVal = (float) str_replace(',', '.', str_replace('"', '', $fields[12]));

                    $this->importRadioReading($objectId, $devid, date('Y-m-d H:i:s', strtotime($rawDate)), $curVal, $prevVal);
                    $imported++;
                }

                $cycle++;

                if ($cycle === 5) {
                    $cycle = 1;
                }
            }

            $firstRow = false;
        }

        fclose($handle);

        return $imported;
    }

    /**
     * Import a legacy Siemens heat-meter CSV export (importSiemensREP.class.php).
     * Tab-delimited, "x x x" marks an absent value, dates can be 2- or 4-digit years.
     */
    public function importSiemensCsv(int $objectId, string $filePath): int
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return 0;
        }

        $skippingHeader = true;
        $headerLinesSeen = 1;
        $imported = 0;

        while (($line = fgets($handle)) !== false) {
            $fields = explode("\t", $line);

            if (! $skippingHeader && count($fields) > 10 && trim($fields[2]) !== 'x x x') {
                $devid = (int) $fields[4];
                $date = strlen($fields[2]) === 8
                    ? \DateTime::createFromFormat('d.m.y H:i:s', $fields[2] . ' ' . $fields[3])
                    : \DateTime::createFromFormat('d.m.Y H:i:s', $fields[2] . ' ' . $fields[3]);

                $statDate = null;
                if (trim($fields[18]) !== 'x x x') {
                    $parsed = \DateTime::createFromFormat('d.m.Y', trim($fields[18]));
                    $statDate = $parsed ? $parsed->format('Y-m-d') : null;
                }

                $errorCode = null;
                $errorDate = null;
                if (trim($fields[8]) !== 'x x x') {
                    $parsed = \DateTime::createFromFormat('d.m.Y', trim($fields[8]));
                    $errorDate = $parsed ? $parsed->format('Y-m-d') : null;
                    $errorCode = (int) $fields[7];
                }

                $insert = false;
                $curVal = $prevVal = $prevVal2 = 0.0;

                if (trim($fields[12]) !== 'x x x') {
                    $curVal = $this->siemensFieldValue($fields[12]);
                    $prevVal = $this->siemensFieldValue($fields[19]);
                    $prevVal2 = $this->siemensFieldValue($fields[20]);
                    $insert = true;
                } elseif (trim($fields[10]) !== 'x x x' && in_array(trim($fields[11]), ['H.C.A.', 'HCA', 'kWh'], true)) {
                    $curVal = $this->siemensFieldValue($fields[10]);
                    $prevVal = $this->siemensFieldValue($fields[19]);
                    $prevVal2 = $this->siemensFieldValue($fields[20]);
                    $insert = true;
                }

                if ($insert && $date !== false) {
                    $this->importRadioReading($objectId, $devid, $date->format('Y-m-d H:i:s'), $curVal, $prevVal, $prevVal2, $errorCode, $errorDate, $statDate);
                    $imported++;
                }
            }

            $headerLinesSeen++;

            if ($headerLinesSeen > 4 && count($fields) > 10 && trim($fields[0]) !== '') {
                $skippingHeader = false;
            }
        }

        fclose($handle);

        return $imported;
    }

    private function siemensFieldValue(string $field): float
    {
        $field = trim($field);

        if ($field === 'x x x' || $field === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', $field);
    }

    /**
     * Shared insert path for the CSV importers above, mirroring
     * RadioModels::importRadioData()/importRadioDataExt(): scales the raw value
     * by device type, skips readings older than what's already stored, and
     * writes both the history log and the "last known value" row.
     */
    private function importRadioReading(
        int $objectId,
        int $devid,
        string $dateTime,
        float $value,
        ?float $mvalue = null,
        ?float $prevValue = null,
        ?int $errorCode = null,
        ?string $errorDate = null,
        ?string $statDate = null
    ): void {
        if (! $this->hasLegacyDeviceTables($objectId)) {
            return;
        }

        $device = DB::table('object_' . $objectId)->where('devid', $devid)->first();

        if ($device === null) {
            return;
        }

        $scale = $this->deviceScale((int) $device->devtype);

        $existing = DB::table('lastdata_' . $objectId)->where('devid', $devid)->first();

        if ($existing !== null && strtotime($existing->date) >= strtotime($dateTime)) {
            return;
        }

        DB::table('mlog_' . $objectId)->insert([
            'devid' => $devid,
            'date' => $dateTime,
            'value' => $value * $scale,
        ]);

        $payload = [
            'date' => $dateTime,
            'value' => $value * $scale,
            'inserterd' => now(),
        ];

        if ($mvalue !== null) {
            $payload['mvalue'] = $mvalue * $scale;
        }

        if ($prevValue !== null) {
            $payload['prevVal'] = $prevValue * $scale;
        }

        if ($errorCode !== null) {
            $payload['error'] = $errorCode;
        }

        if ($errorDate !== null) {
            $payload['errorDate'] = $errorDate;
        }

        if ($statDate !== null) {
            $payload['statDate'] = $statDate;
        }

        DB::table('lastdata_' . $objectId)->updateOrInsert(['devid' => $devid], $payload);
    }

    /**
     * SOE heat-cost allocation, ported from soe.class.php (CreateTMPtable / FillXLS /
     * FillCSV / AndurValue). Splits a monthly payment between an m2-based share and
     * a sensor-reading-based share, then distributes each apartment's cost using its
     * area and its radiators' consumption.
     */
    public function calculateSoeCosts(int $objectId, int $month, float $paymentAmount): array
    {
        $empty = [
            'address' => '', 'city' => '', 'month' => $month, 'payment' => $paymentAmount,
            'm2_cost' => 0.0, 'andur_cost' => 0.0, 'one_m2_price' => 0.0, 'one_pips_price' => 0.0,
            'flats' => [], 'grand' => ['m2' => 0.0, 'm2_cost' => 0.0, 'sensor_cost' => 0.0, 'total' => 0.0],
        ];

        if (! Schema::hasTable('object_' . $objectId) || ! Schema::hasTable('heat_' . $objectId) || ! Schema::hasTable('flat_' . $objectId)) {
            return $empty;
        }

        $object = DB::table('objects')->where('id', $objectId)->first();

        if ($object === null) {
            return $empty;
        }

        $dtype = (int) $object->dtype;
        $settings = $this->getSoeSettings($objectId);
        $m2Pct = $settings['m2_source'];
        $andurPct = 100 - $m2Pct;

        $m2Cost = round($m2Pct * $paymentAmount / 100, 2);
        $andurCost = round($andurPct * $paymentAmount / 100, 2);

        // Apator allocators are read as an absolute lifetime value (no diffing needed,
        // so the "previous reading" is always treated as 0); Siemens allocators
        // report incrementally and need last month's reading subtracted out.
        $resetPrevVal = $month === 1 || $dtype === 1;
        $yearShift = $month === 12 ? 12 : 0;

        $devices = DB::table('object_' . $objectId . ' as O')
            ->leftJoin('heat_' . $objectId . ' as HE', 'O.devid', '=', 'HE.devid')
            ->leftJoin('lastdata_' . $objectId . ' as L', 'O.devid', '=', 'L.devid')
            ->where(function ($query): void {
                $query->where('O.devtype', 3)->orWhereBetween('O.devtype', [30, 39]);
            })
            ->select(['O.location', 'O.devid', 'HE.power', 'HE.cof', 'HE.size', 'HE.description', 'L.date', 'L.value', 'L.mvalue', 'L.prevVal'])
            ->get();

        $deviceRows = [];
        $totalConsumptionUnits = 0.0;

        foreach ($devices as $device) {
            $prevVal = $resetPrevVal ? 0.0 : (float) ($device->prevVal ?? 0);
            $readingMonth = $device->date ? (int) date('n', strtotime($device->date)) : 0;
            $dataIsCurrentOrNewer = ($readingMonth + $yearShift) > $month;

            if ($dtype === 1) {
                $min = 0.0;
                $max = $dataIsCurrentOrNewer ? (float) ($device->mvalue ?? 0) : (float) ($device->value ?? 0);
            } else {
                $min = $prevVal;
                $max = (float) ($device->mvalue ?? 0);
            }

            $diff = $max - $min;
            $power = (float) ($device->power ?? 0);
            $cof = (float) ($device->cof ?? 0);
            $consumption = $power * $cof * $diff / 1000;
            $totalConsumptionUnits += $consumption;

            $deviceRows[] = [
                'location' => (string) $device->location,
                'devid' => $device->devid,
                'power' => $power,
                'cof' => $cof,
                'min' => $min,
                'max' => $max,
                'diff' => $diff,
                'consumption' => $consumption,
            ];
        }

        $onePipsPrice = $totalConsumptionUnits > 0 ? $andurCost / $totalConsumptionUnits : 0.0;

        $flats = DB::table('flat_' . $objectId)->orderBy('id')->get();
        $totalM2 = (float) DB::table('flat_' . $objectId)->sum('size');
        $oneM2Price = $totalM2 > 0 ? $m2Cost / $totalM2 : 0.0;

        $result = [];
        $grand = ['m2' => 0.0, 'm2_cost' => 0.0, 'sensor_cost' => 0.0, 'total' => 0.0];

        foreach ($flats as $flat) {
            $flatDevices = array_values(array_filter($deviceRows, static fn (array $row): bool => $row['location'] === (string) $flat->location));
            $m2Value = $flat->size * $oneM2Price;
            $sensorValue = array_sum(array_map(static fn (array $row): float => $row['consumption'] * $onePipsPrice, $flatDevices));
            $total = $m2Value + $sensorValue;

            $grand['m2'] += $flat->size;
            $grand['m2_cost'] += $m2Value;
            $grand['sensor_cost'] += $sensorValue;
            $grand['total'] += $total;

            $result[] = [
                'location' => $flat->location,
                'size' => $flat->size,
                'devices' => $flatDevices,
                'm2_value' => $m2Value,
                'sensor_value' => $sensorValue,
                'total' => $total,
            ];
        }

        return [
            'address' => $object->address ?? '',
            'city' => $object->City ?? '',
            'month' => $month,
            'payment' => $paymentAmount,
            'm2_cost' => $m2Cost,
            'andur_cost' => $andurCost,
            'one_m2_price' => $oneM2Price,
            'one_pips_price' => $onePipsPrice,
            'flats' => $result,
            'grand' => $grand,
        ];
    }

    public function normalizeFilters(array $filters): array
    {
        return [
            'cradio' => (int) ($filters['cradio'] ?? 0),
            'mradio' => (int) ($filters['mradio'] ?? 0),
            'chradio' => (int) ($filters['chradio'] ?? 0),
        ];
    }

    public function getObjects(array $filters): array
    {
        $query = DB::table('objects')
            ->select(['id', 'address', 'City', 'Description', 'selDate', 'ver', 'dtype'])
            ->where('status', '>', 0)
            ->orderBy('address');

        if (($filters['cradio'] ?? 0) > 0 && ($filters['cradio'] ?? 0) < 3) {
            $query->where('dtype', $filters['cradio']);
        }

        if (($filters['cradio'] ?? 0) === 3) {
            $query->where('dtype', 2)
                ->where(function ($subQuery): void {
                    $subQuery->where('ver', 'like', '0.4%')
                        ->orWhere('ver', 'like', '0.5%');
                });
        }

        if (($filters['cradio'] ?? 0) === 4 || ($filters['cradio'] ?? 0) === 5) {
            $query->where('dtype', ($filters['cradio'] - 1));
        }

        if (($filters['mradio'] ?? 0) > 0) {
            $query->where('manager', $filters['mradio']);
        }

        if (($filters['chradio'] ?? 0) === 1) {
            $query->whereNotNull('selDate');
        }

        if (($filters['chradio'] ?? 0) === 2) {
            $query->whereNull('selDate');
        }

        return $query->get()->map(function ($record): array {
            $errorCount = $this->countErrors((int) $record->id);
            $waterErrors = $this->countWaterErrors((int) $record->id);
            $heatErrors = $this->countHeatErrors((int) $record->id);

            return [
                'id' => $record->id,
                'address' => $record->address,
                'city' => $record->City,
                'description' => nl2br((string) $record->Description),
                'fw' => $record->ver,
                'selected' => $record->selDate !== null ? 'table-success' : '',
                'errors' => $errorCount,
                'water_errors' => $waterErrors,
                'heat_errors' => $heatErrors,
                'dtype' => (int) $record->dtype,
            ];
        })->all();
    }

    public function getObject(int $objectId): ?array
    {
        $object = DB::table('objects')
            ->select(['id', 'address', 'City', 'Description', 'ver', 'dtype'])
            ->where('id', $objectId)
            ->where('status', '>', 0)
            ->first();

        if ($object === null) {
            return null;
        }

        return [
            'id' => $object->id,
            'address' => $object->address,
            'city' => $object->City,
            'description' => $object->Description,
            'fw' => $object->ver,
            'dtype' => (int) $object->dtype,
        ];
    }

    public function getDevices(int $objectId, int $dtype): array
    {
        if (! $this->hasLegacyDeviceTables($objectId)) {
            return [];
        }

        $table = 'object_' . $objectId;
        $lastDataJoin = 'lastdata_' . $objectId;

        // Multi-tariff columns only exist on lastdata_N tables for objects that had
        // a multi-tariff electric meter installed - most objects don't have them.
        $hasTariffColumns = Schema::hasColumn($lastDataJoin, 'tariff_1');
        $tariffColumns = $hasTariffColumns
            ? ', L.tariff_1, L.tariff_2, L.tariff_1_mvalue, L.tariff_2_mvalue'
            : ', NULL as tariff_1, NULL as tariff_2, NULL as tariff_1_mvalue, NULL as tariff_2_mvalue';

        try {
            $query = DB::table($table . ' as O')
                ->leftJoin($lastDataJoin . ' as L', 'O.devid', '=', 'L.devid')
                ->orderBy('O.id')
                ->selectRaw('O.devid, O.id, O.location, O.devtype, L.date, L.value, L.mvalue, L.prevVal, L.inserterd, L.error, L.errorDate, L.statDate, if(L.date < DATE_SUB(NOW(), interval 3 DAY) or (L.date is null), 1, 0) as err' . $tariffColumns);

            return $query->get()->map(function ($record): array {
                $scale = $this->deviceScale((int) $record->devtype);
                $deviceType = (int) $record->devtype;

                return [
                    'id' => $record->id,
                    'devid' => $record->devid,
                    'location' => $record->location,
                    'devtype' => $deviceType,
                    'devtype_class' => $this->deviceClass((int) $record->devtype),
                    'date' => $record->date ? date('H:i:s d.m.Y', strtotime($record->date)) : '',
                    'value' => isset($record->value) ? $record->value / $scale : 0,
                    'mvalue' => isset($record->mvalue) ? $record->mvalue / $scale : 0,
                    'prev_mvalue' => isset($record->prevVal) ? $record->prevVal / $scale : 0,
                    'err_class' => (int) $record->err === 1 ? 'table-danger' : '',
                    'rtime' => $record->inserterd ? date('H:i:s d.m.Y', strtotime($record->inserterd)) : '',
                    'etime' => $record->errorDate ? date('d.m.Y', strtotime($record->errorDate)) : '',
                    'ecode' => $record->error,
                    'sdate' => $record->statDate ? date('d.m.Y', strtotime($record->statDate)) : '',
                    't1' => isset($record->tariff_1) ? $record->tariff_1 / $scale : 0,
                    't2' => isset($record->tariff_2) ? $record->tariff_2 / $scale : 0,
                    't1m' => isset($record->tariff_1_mvalue) ? $record->tariff_1_mvalue / $scale : 0,
                    't2m' => isset($record->tariff_2_mvalue) ? $record->tariff_2_mvalue / $scale : 0,
                ];
            })->all();
        } catch (QueryException $e) {
            return [];
        }
    }

    private function countErrors(int $objectId): int
    {
        if (! $this->hasLegacyDeviceTables($objectId)) {
            return 0;
        }

        try {
            return (int) DB::table('object_' . $objectId . ' as O')
                ->leftJoin('lastdata_' . $objectId . ' as L', 'O.devid', '=', 'L.devid')
                ->whereNotNull('L.error')
                ->count();
        } catch (QueryException $e) {
            return 0;
        }
    }

    private function countWaterErrors(int $objectId): int
    {
        if (! $this->hasLegacyDeviceTables($objectId)) {
            return 0;
        }

        try {
            return (int) DB::table('object_' . $objectId . ' as O')
                ->leftJoin('lastdata_' . $objectId . ' as L', 'O.devid', '=', 'L.devid')
                ->where(function ($query): void {
                    $query->where('L.date', '<', DB::raw('DATE_SUB(NOW(), interval 3 DAY)'))
                        ->orWhereNull('L.inserterd');
                })
                ->where(function ($query): void {
                    $query->where('O.devtype', 1)->orWhere('O.devtype', 2);
                })
                ->count();
        } catch (QueryException $e) {
            return 0;
        }
    }

    private function countHeatErrors(int $objectId): int
    {
        if (! $this->hasLegacyDeviceTables($objectId)) {
            return 0;
        }

        try {
            return (int) DB::table('object_' . $objectId . ' as O')
                ->leftJoin('lastdata_' . $objectId . ' as L', 'O.devid', '=', 'L.devid')
                ->where(function ($query): void {
                    $query->where('L.date', '<', DB::raw('DATE_SUB(NOW(), interval 3 DAY)'))
                        ->orWhereNull('L.inserterd');
                })
                ->where(function ($query): void {
                    $query->where('O.devtype', 3)
                        ->orWhereBetween('O.devtype', [30, 39]);
                })
                ->count();
        } catch (QueryException $e) {
            return 0;
        }
    }

    public function getObjectErrorCounts(int $objectId): array
    {
        return [
            'errors' => $this->countErrors($objectId),
            'water_errors' => $this->countWaterErrors($objectId),
            'heat_errors' => $this->countHeatErrors($objectId),
        ];
    }

    private function hasLegacyDeviceTables(int $objectId): bool
    {
        return Schema::hasTable('object_' . $objectId) && Schema::hasTable('lastdata_' . $objectId);
    }

    /**
     * Matches AllObjPage.class.php's device-type switch exactly: water meters
     * (1, 2, and their 10-19/20-29 sub-ranges) store raw values scaled by 1000,
     * heat (3, 31-39) and generic (8) are unscaled, 4/5 by 100, 6 by 10, 7 by 1000.
     */
    private function deviceScale(int $deviceType): int
    {
        return match (true) {
            $deviceType === 1, $deviceType === 2 => 1000,
            $deviceType >= 10 && $deviceType < 20 => 1000,
            $deviceType >= 20 && $deviceType < 30 => 1000,
            $deviceType === 4, $deviceType === 5 => 100,
            $deviceType === 6 => 10,
            $deviceType === 7 => 1000,
            default => 1,
        };
    }

    private function deviceClass(int $deviceType): string
    {
        return match (true) {
            $deviceType === 1, $deviceType >= 10 && $deviceType < 20 => 'btext',
            $deviceType === 2, $deviceType >= 20 && $deviceType < 30 => 'rtext',
            $deviceType === 3, $deviceType >= 31 && $deviceType <= 39 => 'htext',
            $deviceType === 4, $deviceType === 8 => 'btext',
            $deviceType === 5 => 'rtext',
            $deviceType === 6, $deviceType === 7 => 'hmtext',
            default => 'btext',
        };
    }
}