<?php
$accounts = [
    'admin'   => 'admin123',
    'alice'   => 'alice456',
    'bob'     => 'bob789',
    'charlie' => 'charlie000',
];
foreach ($accounts as $user => $plain) {
    $hash = password_hash($plain, PASSWORD_BCRYPT);
    echo "$user => $hash" . PHP_EOL;
}
