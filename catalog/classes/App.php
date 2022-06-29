<?php

class App
{
    private $theme;
    private $dbh;
    private $description;
    private $language;

    public function __construct($theme, $language, $description, $dbh) {
        $this->theme = $theme;
        $this->dbh = $dbh;
        $this->description = $description;
        $this->language = $language;
    }

    public function getThemePath() {
        return "/content/themes/{$this->theme}";
    }

    public function getSiteLogo() {
        return $this->getThemePath() . "/assets/img/logo.png";
    }

    public function getSeoDescription() {
        return $this->description;
    }

    public function getLanguage() {
        return require_once $_SERVER['DOCUMENT_ROOT'] . $this->getThemePath() . "/language/{$this->language}-{$this->language}/{$this->language}.php";
    }

    public function getComponent($component) {
        return $this->dbh->query("SELECT * FROM `$component`")->fetchAll();
    }

    public function getPurchases($count) {
        return $this->dbh->query("SELECT * FROM `purchases` WHERE `payment_status` = 'APPROVED' ORDER BY `id` DESC LIMIT {$count} ");
    }
}