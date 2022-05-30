<?php

namespace DavidGlitch04\EpicEssentialsTP\provider;

use DavidGlitch04\EpicEssentialsTP\Loader;
use pocketmine\Server;
use pocketmine\utils\Config;
use SQLite3;

class SQLiteProvider
{
    public SQLite3 $db2;

    protected Loader $loader;

    public Config $config;

    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    public function initConfig()
    {
        $this->checkConfig();
        try {
            if (!file_exists($this->loader->getDataFolder().$this->config->get("sqlite-dbname").'.db')) {
                $this->db2 = new \SQLite3($this->loader->getDataFolder().$this->config->get("sqlite-dbname").'.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            } else {
                $this->db2 = new \SQLite3($this->loader->getDataFolder().$this->config->get("sqlite-dbname").'.db', SQLITE3_OPEN_READWRITE);
            }
        } catch (\Throwable $e) {
            $this->loader->getLogger()->critical($e->getMessage());
            Server::getInstance()->getPluginManager()->disablePlugin($this->loader);
            return;
        }
        $this->create_db();
    }

    public function checkConfig(): void
    {
        $this->loader->saveDefaultConfig();
        $this->config = new Config($this->loader->getDataFolder()."config.yml", Config::YAML, array());
        $this->config->set('plugin-name', "EpicessentalsTp+");
        $this->config->save();

        if (!$this->config->get("sqlite-dbname")) {
            $this->config->set("sqlite-dbname", "epicessentials_tp");
            $this->config->save();
        }

        if ($this->config->get("tpa-here-cooldown") == false) {
            $this->config->set("tpa-here-cooldown", "30");
            $this->config->save();
        }
        if ($this->config->get("tp-home-cooldown") == false) {
            $this->config->set("tp-home-cooldown", "5");
            $this->config->save();
        }
        if ($this->config->get("tp-warp-cooldown") == false) {
            $this->config->set("tp-warp-cooldown", "5");
            $this->config->save();
        }
        if ($this->config->get("tp-spawn-cooldown") == false) {
            $this->config->set("tp-spawn-cooldown", "5");
            $this->config->save();
        }
        if ($this->config->get("MOTD") == false) {
            $this->config->set("MOTD", "EssintialsTP+ Welcomes you please change this motd in config");
            $this->config->save();
        }
        if ($this->config->get("wild-MaxX") == false) {
            $this->config->set("wild-MaxX", "300");
            $this->config->save();
        }
        if ($this->config->get("wild-MaxY") == false) {
            $this->config->set("wild-MaxY", "300");
            $this->config->save();
        }
    }

    public function fetchall(): array
    {
        $row = array();

        $i = 0;

        while ($res = $this->loader->result->fetchArray(SQLITE3_ASSOC)) {
            $row[$i] = $res;
            $i++;
        }
        return $row;
    }

    public function update_cooldown($name, $time, $type): bool
    {
        $this->loader->prepare = $this->db2->prepare("SELECT home,warp,spawn,player FROM cooldowns WHERE player =:name");
        $this->loader->prepare->bindValue(":name", $name, SQLITE3_TEXT);
        $this->loader->result = $this->loader->prepare->execute();
        $sql = $this->fetchall();
        if (count($sql) > 0) {
            switch ($type) {
                case 'home':
                    $this->loader->prepare = $this->db2->prepare("UPDATE cooldowns SET home = :time WHERE player = :name");
                    $this->loader->prepare->bindValue(":name", $name, SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":time", time(), SQLITE3_INTEGER);
                    $this->loader->result = $this->loader->prepare->execute();
                    return true;
                case 'warp':
                    $this->loader->prepare = $this->db2->prepare("UPDATE cooldowns SET warp = :time WHERE player = :name");
                    $this->loader->prepare->bindValue(":name", $name, SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":time", time(), SQLITE3_INTEGER);
                    $this->loader->result = $this->loader->prepare->execute();
                    return true;
                case 'spawn':
                    $this->loader->prepare = $this->db2->prepare("UPDATE cooldowns SET spawn = :time WHERE player = :name");
                    $this->loader->prepare->bindValue(":name", $name, SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":time", time(), SQLITE3_INTEGER);
                    $this->loader->result = $this->loader->prepare->execute();
                    return true;
                default:
                    return false;
            }
        } else {
            switch ($type) {
                case 'home':
                    $this->loader->prepare = $this->db2->prepare("INSERT INTO cooldowns (home, warp, spawn, player) VALUES (:time, 0, 0, :name)");
                    $this->loader->prepare->bindValue(":time", time(), SQLITE3_INTEGER);
                    $this->loader->prepare->bindValue(":name", $name, SQLITE3_TEXT);

                    $this->loader->result = $this->loader->prepare->execute();
                    return true;
                case 'warp':
                    $this->loader->prepare = $this->db2->prepare("INSERT INTO cooldowns (home, warp, spawn, player) VALUES (0, :time, 0, :name)");
                    $this->loader->prepare->bindValue(":time", time(), SQLITE3_INTEGER);
                    $this->loader->prepare->bindValue(":name", $name, SQLITE3_TEXT);

                    $this->loader->result = $this->loader->prepare->execute();
                    return true;
                case 'spawn':
                    $this->loader->prepare = $this->db2->prepare("INSERT INTO cooldowns (home, warp, spawn, player) VALUES (0, 0, :time, :name)");
                    $this->loader->prepare->bindValue(":time", time(), SQLITE3_INTEGER);
                    $this->loader->prepare->bindValue(":name", $name, SQLITE3_TEXT);

                    $this->loader->result = $this->loader->prepare->execute();
                    return true;
                default:
                    return false;
            }
        }
    }
    public function create_db(): void
    {
        $this->loader->prepare = $this->db2->prepare("SELECT * FROM sqlite_master WHERE type='table' AND name='homes'");
        $this->loader->result = $this->loader->prepare->execute();
        $sql = $this->fetchall();
        $count = count($sql);
        if ($count == 0) {
            $this->loader->prepare = $this->db2->prepare("CREATE TABLE homes (
                      id INTEGER PRIMARY KEY,
                      player TEXT,
                      x TEXT,
                      y TEXT,
                      z TEXT,
                      title TEXT,
                      world TEXT)");
            $this->loader->result = $this->loader->prepare->execute();
        }
        $this->loader->prepare = $this->db2->prepare("SELECT * FROM sqlite_master WHERE type='table' AND name='tp_requests'");
        $this->loader->result = $this->loader->prepare->execute();
        $sql2 = $this->fetchall();
        $count2 = count($sql2);
        if ($count2 == 0) {
            $this->loader->prepare = $this->db2->prepare("CREATE TABLE tp_requests (
                      id INTEGER PRIMARY KEY,
                      player TEXT,
                      player_from TEXT,
                      type TEXT,
                      time TEXT,
                      status TEXT)");
            $this->loader->result = $this->loader->prepare->execute();
        }
        $this->loader->prepare = $this->db2->prepare("SELECT * FROM sqlite_master WHERE type='table' AND name='warps'");
        $this->loader->result = $this->loader->prepare->execute();
        $sql3 = $this->fetchall();
        $count3 = count($sql3);
        if ($count3 == 0) {
            $this->loader->prepare = $this->db2->prepare("CREATE TABLE warps (
                      id INTEGER PRIMARY KEY,
                      x TEXT,
                      y TEXT,
                      z TEXT,
                      world TEXT,
                      title TEXT)");
            $this->loader->result = $this->loader->prepare->execute();
        }
        $this->loader->prepare = $this->db2->prepare("SELECT * FROM sqlite_master WHERE type='table' AND name='spawns'");
        $this->loader->result = $this->loader->prepare->execute();
        $sql4 = $this->fetchall();
        $count4 = count($sql4);
        if ($count4 == 0) {
            $this->loader->prepare = $this->db2->prepare("CREATE TABLE spawns (
                      id INTEGER PRIMARY KEY,
                      x TEXT,
                      y TEXT,
                      z TEXT,
                      world TEXT
                      )");
            $this->loader->result = $this->loader->prepare->execute();
        }
        $this->loader->prepare = $this->db2->prepare("SELECT * FROM sqlite_master WHERE type='table' AND name='cooldowns'");
        $this->loader->result = $this->loader->prepare->execute();
        $sql5 = $this->fetchall();
        $count5 = count($sql5);
        if ($count5 == 0) {
            $this->loader->prepare = $this->db2->prepare("CREATE TABLE cooldowns (
                      id INTEGER PRIMARY KEY,
                      home INTEGER,
                      warp INTEGER,
                      spawn INTEGER,
                      player TEXT
                      )");
            $this->loader->result = $this->loader->prepare->execute();
        }
    }
}
