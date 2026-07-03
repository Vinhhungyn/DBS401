<?php
$c = new mysqli('mysql-patched', 'app_user', 'app123', 'company_db_patched', 3306);
if ($c->connect_error) {
    echo 'ERR: ' . $c->connect_error;
} else {
    echo 'OK - Connected!';
    $r = $c->query('SELECT username, password FROM employees');
    while ($row = $r->fetch_assoc()) {
        echo PHP_EOL . $row['username'] . ' => ' . $row['password'];
    }
}
