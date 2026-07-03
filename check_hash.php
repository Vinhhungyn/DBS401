<?php
$c = new mysqli('mysql-patched', 'root', 'root123', 'company_db_patched', 3306);
$r = $c->query('SELECT username, password FROM employees');
while ($row = $r->fetch_assoc()) {
    $ok = password_verify('admin123', $row['password']) ? 'OK' : 'FAIL';
    echo $row['username'] . ' => ' . $ok . ' | hash: ' . $row['password'] . PHP_EOL;
}
