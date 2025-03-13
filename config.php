<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

return [
    'client_id' => $_ENV['CLIENT_ID'],
    'client_secret' => $_ENV['CLIENT_SECRET'],
    'token_url' => $_ENV['TOKEN_URL'],
    'third_party_api' => $_ENV['THIRD_PARTY_API']
];
