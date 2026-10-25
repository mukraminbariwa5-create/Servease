<?php
// Test helper: runs a query against the app database and prints JSON
// (rows for SELECT, {"affected": n} for anything else, e.g. moving a test booking to today).
require __DIR__ . '/../app/bootstrap.php';
$sql = $argv[1];
if (preg_match('/^\s*(SELECT|SHOW)\b/i', $sql)) echo json_encode(DB::all($sql));
else echo json_encode(['affected' => DB::query($sql)->rowCount()]);
