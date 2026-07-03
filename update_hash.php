<?php
$c = new mysqli('mysql-patched', 'root', 'root123', 'company_db_patched', 3306);
$updates = [
    ['admin',   '.H9.CQNLQA6FDyeONV.XL2tB6ghpDy32'],
    ['alice',   '/WRilYcgCkmdzkuUfZ4wMtm8JtIX8MS8.lE2nhVMBGh8TG'],
    ['bob',     '.RU4sXxYkpGZqOKlIKSi1qwQFenPIxTW'],
    ['charlie', '/T9RrLu6jNkxCeNx4uzIgUa2nTFRSGPFB8WYcfcrGxG92'],
];
foreach ($updates as [$user, $hash]) {
    $stmt = $c->prepare('UPDATE employees SET password = ? WHERE username = ?');
    $stmt->bind_param('ss', $hash, $user);
    $stmt->execute();
    echo "Updated: $user" . PHP_EOL;
}
echo 'Done!';
