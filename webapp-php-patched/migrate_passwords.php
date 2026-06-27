<?php
// migrate_passwords.php — Hash lai password cu trong DB
// Chay 1 lan: php migrate_passwords.php (hoac truy cap qua browser 1 lan)
// Sau do XOA hoac BLOCK file nay!
require_once 'config.php';

$conn = get_conn();
$result = $conn->query("SELECT id, password FROM employees");

$updated = 0;
$skipped = 0;

while ($row = $result->fetch_assoc()) {
    $id  = $row['id'];
    $pwd = $row['password'];

    // Neu da la bcrypt hash thi bo qua
    if (str_starts_with($pwd, '$2y$') || str_starts_with($pwd, '$2b$')) {
        $skipped++;
        continue;
    }

    // Hash bang bcrypt
    $hash = password_hash($pwd, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE employees SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $id);
    $stmt->execute();
    $stmt->close();
    $updated++;
}

$conn->close();

echo "<pre>";
echo "Migration hoan tat!\n";
echo "Da hash: {$updated} tai khoan\n";
echo "Bo qua (da hash): {$skipped} tai khoan\n";
echo "\n!!! HAY XOA FILE NAY NGAY SAU KHI CHAY !!!\n";
echo "</pre>";