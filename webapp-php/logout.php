<?php
// ============================================================
// logout.php — Xoá session và redirect về login
// Tương đương: @app.route("/logout")
// ============================================================
require_once 'config.php';

session_destroy();
header('Location: /login.php');
exit;
