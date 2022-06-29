<?php


class ServerInfo
{
    private $ip;
    private $port;
    private $domain;
    private $serverName;

    public function __construct($ip, $port, $domain) {
        $this->ip = $ip;
        $this->port = $port;
        $this->domain = $domain;
    }

    public function setServerName($value) {
        $this->serverName = $value;
    }

    public function getServerName() {
        return $this->serverName;
    }

    private function getServer() {
        return json_decode(file_get_contents("https://api.mcsrvstat.us/2/{$this->ip}:{$this->port}"), true);
    }

    public function getServerStatus() {
        if ($this->getServer()['online'] == 1) {
            return 'Online';
        } else {
            return 'Offline';
        }
    }

    public function getPlayers() {
        return $this->getServer()['players'];
    }

    public function getIp(bool $port) {
        if ($port) {
            return $this->getServer()['ip'] . ':' . $this->getServer()['port'];
        } else {
            return $this->getServer()['ip'];
        }
    }

    public function getDomain() {
        return $this->domain;
    }

    public function test() {
        print_r($this->getServer());
    }
}
