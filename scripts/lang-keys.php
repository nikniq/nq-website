<?php
// Lists every translation key used in the app (outside admin) as a JSON array.
// Usage: php scripts/lang-keys.php > keys.json ; php scripts/lang-keys.php --missing  (keys absent from any lang/*.json)
$root = dirname(__DIR__);
$files = [];
foreach (['app', 'resources/views', 'bootstrap'] as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$dir", FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $p = $f->getPathname();
        if (preg_match('/\.php$/', $p) && ! preg_match('#/(Admin|admin)/#', $p) && ! str_contains($p, 'bootstrap/cache')) $files[] = $p;
    }
}
$keys = [];
foreach ($files as $p) {
    $src = file_get_contents($p);
    preg_match_all("/(?:__|trans_choice|@lang)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", $src, $m1);
    preg_match_all('/(?:__|trans_choice|@lang)\(\s*"((?:[^"\\\\]|\\\\.)*)"/', $src, $m2);
    foreach ($m1[1] as $k) $keys[stripslashes($k)] = true;
    foreach ($m2[1] as $k) $keys[stripcslashes($k)] = true;
}
$keys = array_keys($keys);
// Skip framework group keys like 'validation.required'.
$keys = array_values(array_filter($keys, fn ($k) => ! preg_match('/^[a-z_]+\.[a-z_.]+$/', $k)));
sort($keys);
if (in_array('--missing', $argv)) {
    foreach (glob("$root/lang/*.json") as $j) {
        $have = json_decode(file_get_contents($j), true) ?: [];
        $miss = array_diff($keys, array_keys($have));
        echo basename($j).': '.count($miss)." missing\n";
        foreach ($miss as $k) echo "  $k\n";
    }
    exit(count($keys) ? 0 : 1);
}
echo json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
