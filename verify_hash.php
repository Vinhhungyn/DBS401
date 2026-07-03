<?php
$hashes = [
    'admin'   => ['admin123',   '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'],
    'alice'   => ['alice456',   '.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm'],
    'bob'     => ['bob789',     '.OvWFMiGgGqT3FXsAZ.5F5E3bGl3.xMme'],
    'charlie' => ['charlie000', '/j5YtMKUrjrR.YXHM9/e9f.P8pBBGZLVJ5GR1hZMVuEcRk6sJmm'],
];
foreach ($hashes as $user => [$plain, $hash]) {
    $ok = password_verify($plain, $hash) ? 'OK' : 'FAIL';
    echo "$user / $plain => $ok" . PHP_EOL;
}
