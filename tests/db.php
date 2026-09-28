<?php
// Test helper: runs a read query against the app database and prints JSON.
require __DIR__ . '/../app/bootstrap.php';
echo json_encode(DB::all($argv[1]));
