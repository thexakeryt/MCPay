<?php

require __DIR__ . '/settings.php';
require __DIR__ . '/catalog/classes/App.php';
require __DIR__ . '/catalog/classes/ServerInfo.php';
require __DIR__ . '/vendor/autoload.php';

$router = new \Bramus\Router\Router();
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . "/content/themes/{$theme}");
$twig = new \Twig\Environment($loader);
$app = new App($theme, $language, $seo_description, $dbh);
$serverInfo = new ServerInfo($ip, $port, $domain);
$serverInfo->setServerName($name);

$router->match('GET', '/', function () use ($twig, $app, $serverInfo) {
    echo $twig->render('index.twig', [
        'theme_path' => $app->getThemePath(),
        'site_logo' => $app->getSiteLogo(),
        'seo_description' => $app->getSeoDescription(),
        'server_status' => $serverInfo->getServerStatus(),
        'server_players' => $serverInfo->getPlayers(),
        'domain' => $serverInfo->getDomain(),
        'server_name' => $serverInfo->getServerName(),
        'server_ip' => $serverInfo->getIp(true),
        'header_nav' => $app->getComponent('header_nav'),
        'footer_nav' => $app->getComponent('footer_nav'),
        'categories' => $app->getComponent('categories'),
        'products' => $app->getComponent('products'),
        'language' => $app->getLanguage(),
        'purchases' => $app->getPurchases(4)
    ]);
});

$router->match('GET', '/success', function () {
    echo 'success';
});

$router->run();