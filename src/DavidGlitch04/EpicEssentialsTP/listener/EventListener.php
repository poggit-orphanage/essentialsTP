<?php

namespace DavidGlitch04\EpicEssentialsTP\listener;

use DavidGlitch04\EpicEssentialsTP\Loader;
use DavidGlitch04\EpicEssentialsTP\provider\SQLiteProvider;
use pocketmine\block\tile\Sign;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class EventListener implements Listener
{
    protected Loader $loader;

    protected SQLiteProvider $provider;

    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
        $this->provider = $this->loader->getProvider();
    }

    public function onPlayerDeath(PlayerDeathEvent $event)
    {
        $player = $event->getEntity();
        $this->loader->death_loc[$player->getName()] = new Position(
            round($player->getPosition()->getX()),
            round($player->getPosition()->getY()),
            round($player->getPosition()->getZ()),
            $player->getWorld()
        );
    }

    public function onPlayerJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $this->provider->update_cooldown($player->getName(), 0, 'home');
        $this->provider->update_cooldown($player->getName(), 0, 'warp');
        $this->provider->update_cooldown($player->getName(), 0, 'spawn');
    }

    public function onPlayerSleep(PlayerBedEnterEvent $event)
    {
        if ($this->provider->config->get("bed-sets-home") == true) {
            $player = $event->getPlayer();
            if ($player->hasPermission("epicessentialstp.command.bedsethome")) {
                $this->player_cords = array('x' => (int) $player->getPosition()->getX(),'y' => (int) $player->getPosition()->getY(),'z' => (int) $player->getPosition()->getZ());
                $this->loader->username = $player->getName();
                $this->loader->world = $player->getWorld()->getProvider();
                $this->loader->home_loc = "bed";
                $this->loader->prepare = $this->provider->db2->prepare("SELECT player,title,x,y,z,world FROM homes WHERE player = :name AND title = :title");
                $this->loader->prepare->bindValue(":name", $this->loader->username, SQLITE3_TEXT);
                $this->loader->prepare->bindValue(":title", $this->loader->home_loc, SQLITE3_TEXT);
                $this->loader->result = $this->loader->prepare->execute();
                $sql = $this->provider->fetchall();
                if (count($sql) > 0) {
                    $this->loader->prepare = $this->provider->db2->prepare("UPDATE homes SET world = :world, title = :title, x = :x, y = :y, z = :z WHERE player = :name AND title = :title");
                    $this->loader->prepare->bindValue(":name", $this->loader->username, SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":title", $this->loader->home_loc, SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":world", $this->loader->world, SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":x", $this->player_cords['x'], SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":y", $this->player_cords['y'], SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":z", $this->player_cords['z'], SQLITE3_TEXT);
                    $this->loader->result = $this->loader->prepare->execute();
                } else {
                    $this->loader->prepare = $this->provider->db2->prepare("INSERT INTO homes (player, title, world, x, y, z) VALUES (:name, :title, :world, :x, :y, :z)");
                    $this->loader->prepare->bindValue(":name", $this->loader->username, SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":title", $this->loader->home_loc, SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":world", $this->loader->world, SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":x", $this->player_cords['x'], SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":y", $this->player_cords['y'], SQLITE3_TEXT);
                    $this->loader->prepare->bindValue(":z", $this->player_cords['z'], SQLITE3_TEXT);
                    $this->loader->result = $this->loader->prepare->execute();
                }
            }
        }
    }

    public function onBlockTap(PlayerInteractEvent $event)
    {
        if ($event->isCancelled()) {
            return true;
        }
        $player = $event->getPlayer();
        $block  = $event->getBlock();
        $blockworld = $block->getPosition()->getWorld();
        $tile = $blockworld->getTile(
            new Vector3(
                $block->getPosition()->getFloorX(),
                $block->getPosition()->getFloorY(),
                $block->getPosition()->getFloorZ()
            )
        );
        if ($tile instanceof Sign) {
            $text = $tile->getText()->getLines();
            if (strtolower($text[0]) === "[warp]") {
                if (!$player->hasPermission("epicessentialstp.command.sign.warp")) {
                    $player->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    $event->cancel();
                    return true;
                }
                $event->cancel();
                $this->loader->prepare = $this->provider->db2->prepare("SELECT home,warp,spawn,player FROM cooldowns WHERE player =:name AND warp < :time");
                $this->loader->prepare->bindValue(":name", $player->getName(), SQLITE3_TEXT);
                $this->loader->prepare->bindValue(":time", (time() - $this->provider->config->get("tp-warp-cooldown")), SQLITE3_TEXT);
                $this->loader->result = $this->loader->prepare->execute();
                $cool_sql = $this->provider->fetchall();
                if (count($cool_sql) > 0) {
                    $this->loader->warp_loc = $text[1];
                    $this->loader->prepare = $this->provider->db2->prepare("SELECT title,x,y,z,world FROM warps WHERE title = :title");
                    $this->loader->prepare->bindValue(":title", $this->loader->warp_loc, SQLITE3_TEXT);
                    $this->loader->result = $this->loader->prepare->execute();
                    $sql = $this->provider->fetchall();
                    if (count($sql) > 0) {
                        $sql = $sql[0];
                        if (isset($sql['world'])) {
                            if (Server::getInstance()->getWorldManager()->getWorldByName($sql['world']) != false) {
                                $curr_world = Server::getInstance()->getWorldManager()->getWorldByName($sql['world']);
                                $pos = new Position((int) $sql['x'], (int) $sql['y'], (int) $sql['z'], $curr_world);
                                $player->teleport($pos);
                                $this->provider->update_cooldown($player->getName(), time(), 'warp');
                                $player->sendMessage($this->provider->config->get("Lang_warp_to") . " " . TextFormat::GOLD . $sql['title']);
                                return true;
                            } else {
                                $player->sendMessage(TextFormat::RED . $this->provider->config->get("Land_chunk_not_loaded"));
                                return true;
                            }
                        }
                    } else {
                        $player->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_warp_listed"));
                        return true;
                    }
                } else {
                    $player->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_action_cooldown"));
                    return true;
                }
            } elseif ((strtolower($text[0]) === "[wild]")) {
                if (!$player->hasPermission("essentialstp.command.sign.wild")) {
                    $player->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    $event->cancel();
                    return true;
                }
                $event->cancel();
                $this->loader->world = $player->getWorld()->getFolderName();
                foreach (Server::getInstance()->getWorldManager()->getWorlds() as $aval_world => $curr_world) {
                    if ($this->loader->world == $curr_world->getFolderName()) {
                        $pos = $player->getWorld()->getSafeSpawn(new Vector3(
                            rand(
                                '-'.$this->provider->config->get("wild-MaxX"),
                                $this->provider->config->get("wild-MaxX")
                            ),
                            rand(70, 100),
                            rand(
                                '-'.$this->provider->config->get("wild-MaxY"),
                                $this->provider->config->get("wild-MaxY")
                            )
                        ));
                        $pos->getWorld()->loadChunk($pos->getX(), $pos->getZ());
                        $pos->getWorld()->getChunk($pos->getX(), $pos->getZ(), true);
                        $pos = $pos->getWorld()->getSafeSpawn(new Vector3($pos->getX(), rand(4, 100), $pos->getZ()));

                        if ($pos->getWorld()->isChunkLoaded($pos->getX(), $pos->getZ())) {
                            $player->teleport($pos->getWorld()->getSafeSpawn(new Vector3($pos->getX(), rand(4, 100), $pos->getZ())));
                            $player->sendMessage($this->provider->config->get("Lang_teleport_wild"));
                            return true;
                        } else {
                            $player->sendMessage($this->provider->config->get("Land_chunk_not_loaded"));
                            return true;
                        }
                    }
                }
            } elseif (strtolower($text[0]) === "[spawn]") {
                if (!$player->hasPermission("epicessentialstp.command.sign.spawn")) {
                    $player->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    $event->cancel();
                    return true;
                }
                $event->cancel();
                $this->loader->prepare = $this->provider->db2->prepare("SELECT home,warp,spawn,player FROM cooldowns WHERE player =:name AND spawn < :time");
                $this->loader->prepare->bindValue(":name", $player->getName(), SQLITE3_TEXT);
                $this->loader->prepare->bindValue(":time", (time() - $this->provider->config->get("tp-spawn-cooldown")), SQLITE3_TEXT);
                $this->loader->result = $this->loader->prepare->execute();
                $cool_sql = $this->provider->fetchall();
                if (count($cool_sql) > 0) {
                    $this->loader->world = $player->getWorld()->getFolderName();
                    $this->loader->prepare = $this->provider->db2->prepare("SELECT x,y,z,world FROM spawns WHERE world = :world");
                    $this->loader->prepare->bindValue(":world", $this->loader->world, SQLITE3_TEXT);
                    $this->loader->result = $this->loader->prepare->execute();
                    $sql = $this->provider->fetchall();
                    if (count($sql) > 0) {
                        $sql = $sql[0];
                        foreach (Server::getInstance()->getWorldManager()->getWorlds() as $aval_world => $curr_world) {
                            if ($sql['world'] == $curr_world->getFolderName()) {
                                $pos = new Position((int) $sql['x'], (int) $sql['y'], (int) $sql['z'], $curr_world);

                                $player->teleport($pos);
                                $this->provider->update_cooldown($player->getName(), time(), 'spawn');
                                $player->sendMessage($this->provider->config->get("Lang_teleport_spawn"));
                                return true;
                            }
                        }
                    } else {
                        $player->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_spawn_set"));
                        $player->teleport($player->getWorld()->getSpawnLocation());
                        $this->provider->update_cooldown($player->getName(), time(), 'spawn');
                        $player->sendMessage($this->provider->config->get("Lang_teleport_spawn_original"));
                        return true;
                    }
                } else {
                    $player->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_action_cooldown"));
                    return true;
                }
            }
        }
        return true;
    }
    public function onBlockBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $block  = $event->getBlock();
        //check tile above is not sign as breaking it will break sign
        $blockworld = $block->getPosition()->getWorld();
        $tile_above = $blockworld->getTile(
            new Vector3(
                $block->getPosition()->getFloorX(),
                ($block->getPosition()->getFloorY()+1),
                $block->getPosition()->getFloorZ()
            )
        );
        if ($tile_above instanceof Sign) {
            $text = $tile_above->getText()->getLines();

            if (strtolower($text[0]) === "[warp]") {
                if (!$player->hasPermission("epicessentialstp.command.sign.warp.break")) {
                    $player->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    $event->cancel();
                    return true;
                } else {
                    return true;
                }
            } elseif (strtolower($text[0]) === "[wild]") {
                if (!$player->hasPermission("epicessentialstp.command.sign.wild.break")) {
                    $player->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    $event->cancel();
                    return true;
                } else {
                    return true;
                }
            } elseif (strtolower($text[0]) === "[spawn]") {
                if (!$player->hasPermission("epicessentialstp.command.sign.spawn.break")) {
                    $player->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    $event->cancel();
                    return true;
                } else {
                    return true;
                }
            }
        }

        //normal tile
        $tile = $blockworld->getTile(
            new Vector3(
                $block->getPosition()->getFloorX(),
                $block->getPosition()->getFloorY(),
                $block->getPosition()->getFloorZ()
            )
        );
        if ($tile instanceof Sign) {
            $text = $tile->getText()->getLines();

            if (strtolower($text[0]) === "[warp]") {
                if (!$player->hasPermission("epicessentialstp.command.sign.warp.break")) {
                    $player->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    $event->cancel();
                    return true;
                } else {
                    return true;
                }
            } elseif (strtolower($text[0]) === "[wild]") {
                if (!$player->hasPermission("epicessentialstp.command.sign.wild.break")) {
                    $player->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    $event->cancel();
                    return true;
                } else {
                    return true;
                }
            } elseif (strtolower($text[0]) === "[spawn]") {
                if (!$player->hasPermission("epicessentialstp.command.sign.spawn.break")) {
                    $player->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    $event->cancel();
                    return true;
                } else {
                    return true;
                }
            }
        }
        return true;
    }

    public function onSignChange(SignChangeEvent $event)
    {
        if ($event->isCancelled()) {
            return true;
        }
        $player = $event->getPlayer();

        if (strtolower($event->getNewText()->getLines()[0]) === "[warp]") {
            if (!$player->hasPermission("epicessentialstp.command.sign.warp.create")) {
                $player->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                $event->cancel();
                return true;
            } else {
                // lets check warp exist first if not don't allow change
                $this->loader->warp_loc = $event->getNewText()->getLines()[1];
                $this->loader->prepare = $this->provider->db2->prepare("SELECT title,x,y,z,world FROM warps WHERE title = :title");
                $this->loader->prepare->bindValue(":title", $this->loader->warp_loc, SQLITE3_TEXT);
                $this->loader->result = $this->loader->prepare->execute();
                $sql = $this->provider->fetchall();
                if (count($sql) > 0) {
                    $player->sendMessage(TextFormat::GREEN.$this->provider->config->get("Lang_warp_set") . " " . $this->loader->warp_loc);
                }
                return true;
            }
        } elseif (strtolower($event->getNewText()->getLines()[0]) === "[wild]") {
            if (!$player->hasPermission("epicessentialstp.command.sign.wild.create")) {
                $player->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                $event->cancel();
                return true;
            } else {
                return true;
            }
        } elseif (strtolower($event->getNewText()->getLines()[0]) === "[spawn]") {
            if (!$player->hasPermission("epicessentialstp.command.sign.spawn.create")) {
                $player->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                $event->cancel();
                return true;
            } else {
                return true;
            }
        }
    }

    public function onPlayerRespawn(PlayerRespawnEvent $event)
    {
        $player = $event->getPlayer();
        if (isset($this->loader->death_loc[$player->getName()])) {
            $this->loader->username = $player->getName();
            $this->loader->prepare = $this->provider->db2->prepare("SELECT player,x,y,z,title,world FROM homes WHERE player =:name AND title =:bed");
            $this->loader->prepare->bindValue(":name", $this->loader->username, SQLITE3_TEXT);
            $this->loader->prepare->bindValue(":bed", 'bed', SQLITE3_TEXT);
            $this->loader->result = $this->loader->prepare->execute();
            $sql = $this->provider->fetchall();
            if (count($sql) > 0) {
                $sql = $sql[0];
                foreach (Server::getInstance()->getWorldManager()->getWorlds() as $aval_world => $curr_world) {
                    if ($sql['world'] == $curr_world->getFolderName()) {
                        $event->setRespawnPosition(new Position((int) $sql['x'], (int) $sql['y'], (int) $sql['z'], $curr_world));
                        $this->provider->update_cooldown($this->loader->username, time(), 'home');
                        $player->sendMessage($this->config->get("Lang_teleport_home"));
                        return true;
                    }
                }
            } else {
                $this->loader->world = $player->getWorld()->getFolderName();
                $this->loader->prepare = $this->provider->db2->prepare("SELECT x,y,z,world FROM spawns WHERE world = :world");
                $this->loader->prepare->bindValue(":world", $this->loader->world, SQLITE3_TEXT);
                $this->loader->result = $this->loader->prepare->execute();
                $sql = $this->provider->fetchall();
                if (count($sql) > 0) {
                    $sql = $sql[0];
                    foreach (Server::getInstance()->getWorldManager()->getWorlds() as $aval_world => $curr_world) {
                        if ($sql['world'] == $curr_world->getFolderName()) {
                            $event->setRespawnPosition(new Position((int) $sql['x'], (int) $sql['y'], (int) $sql['z'], $curr_world));
                            $this->provider->update_cooldown($player->getName(), time(), 'spawn');
                            $player->sendMessage($this->provider->config->get("Lang_teleport_spawn"));
                            return true;
                        }
                    }
                } else {
                    $player->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_spawn_set"));
                    $event->setRespawnPosition($player->getWorld()->getSpawnLocation());
                    $this->provider->update_cooldown($player->getName(), time(), 'spawn');
                    $player->sendMessage($this->provider->config->get("Lang_teleport_spawn_original"));
                    return true;
                }
            }
        }
    }
}
