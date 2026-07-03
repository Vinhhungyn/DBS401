<?php
require "/var/www/html/config.php";
$conn = get_conn();
$result = $conn->query("SELECT id, username, password FROM employees");
while ($row = $result->fetch_assoc()) {
    if (strpos($row["password"], "$2y$") === false) {
        $hashed = password_hash($row["password"], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE employees SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $row["id"]);
        $stmt->execute();
        echo "Updated: " . $row["username"] . PHP_EOL;
    } else {
        echo "Skip: " . $row["username"] . PHP_EOL;
    }
}
$conn->close();
