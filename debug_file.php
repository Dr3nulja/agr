<?php
$file = __DIR__ . '/app/Services/Legacy/LegacyObjectsService.php';
$lines = file($file, FILE_IGNORE_NEW_LINES);
echo "mtime=" . filemtime($file) . "\n";
echo "line595=" . ($lines[594] ?? 'MISSING') . "\n";
echo "contains_getNullable=" . (strpos(implode('\n', $lines), 'getNullableRecordValue') !== false ? 'yes' : 'no') . "\n";
