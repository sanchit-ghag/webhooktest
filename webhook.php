<?php
require 'WebhookHandler.php';
$config = require 'config.php';

$webhook = new WebhookHandler($config);
$webhook->handleIncomingRequest();
?>
