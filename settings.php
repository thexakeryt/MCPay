<?php

// Server settings
$ip = '127.0.0.1';
$port = '25565';
$rcon_port = 25575;
$rcon_password = 'password';
$domain = 'localhost';
$name = 'MCPay';

// Theme settings
$theme = 'default';
$language = 'en';
$seo_description = 'MCPay - the best minecraft server';

// Database settings
$db_host = 'localhost';
$db_user = 'root';
$db_password = 'root';
$db_name = 'mcpay';
$dbh = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_password);

// Shop settings & paypal
$payment_sytem = 'paypal';
$currency = 'EUR';
$paypal_email = 'koistex@gmail.com';
$client_id = 'client_id';
$secret = 'secret_key';
$return_url = 'https://example.com';