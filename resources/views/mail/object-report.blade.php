Tere!

Manuses on {{ mb_strtolower($reportTitle) }}: {{ $object->address }}, {{ $object->City }}.
Koostatud {{ now()->format('d.m.Y H:i') }}.

Lugupidamisega
{{ \App\Models\AgrObject::COMPANIES[(int) $object->Company] ?? \App\Models\AgrObject::COMPANIES[1] }}
