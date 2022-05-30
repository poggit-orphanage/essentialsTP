<?php

namespace DavidGlitch04\EpicEssentialsTP;

use DavidGlitch04\EpicEssentialsTP\listener\EventListener;
use DavidGlitch04\EpicEssentialsTP\provider\SQLiteProvider;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\world\Position;

class Loader extends PluginBase
{
    protected SQLiteProvider $provider;

    public string $tp_sender;

    public string $tp_reciver;

    public string $world;

    public mixed $home_loc;

    public mixed $warp_loc;

    public mixed $death_loc;

    public array $player_cords;

    public string $username;

    public mixed $result;

    public mixed $prepare;

    public mixed $tpa_cooldown;

    public function onEnable(): void
    {
        $this->provider = new SQLiteProvider($this);
        $this->provider->initConfig();
        $this->tpa_cooldown = time() - $this->provider->config->get("tpa-here-cooldown");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    public function onDisable(): void
    {
        if ($this->prepare) {
            $this->prepare->close();
        }
    }

    public function getProvider(): SQLiteProvider
    {
        return $this->provider;
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool
    {
        switch ($cmd->getName()) {
            case 'home':
                if ($sender instanceof Player) {
                    if (!$sender->hasPermission("epicessentialstp.command.home")) {
                        $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                        return true;
                    }
                    $this->username = $sender->getName();
                    if (count($args) == 0) {
                        $this->prepare = $this->provider->db2->prepare("SELECT player,x,y,z,title,world FROM homes WHERE player =:name");
                        $this->prepare->bindValue(":name", $this->username, SQLITE3_TEXT);
                        $this->result = $this->prepare->execute();
                        $sql = $this->provider->fetchall();
                        $home_list = null;
                        foreach ($sql as $ptu) {
                            $home_list .= '['.TextFormat::GOLD.$ptu['title'].TextFormat::WHITE.'] ';
                        }
                        if ($home_list != null) {
                            $sender->sendMessage($this->provider->config->get("Lang_your_homes")." ".$home_list);
                            return true;
                        } else {
                            $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_home_set"));
                            return true;
                        }
                    } else {
                        $this->prepare = $this->provider->db2->prepare("SELECT home,warp,spawn,player FROM cooldowns WHERE player =:name AND home < :time");
                        $this->prepare->bindValue(":name", $this->username, SQLITE3_TEXT);
                        $this->prepare->bindValue(":time", (time() - $this->provider->config->get("tp-home-cooldown")), SQLITE3_INTEGER);
                        $this->result = $this->prepare->execute();
                        $cool_sql = $this->provider->fetchall();
                        if (count($cool_sql) > 0) {
                            $this->home_loc = $args[0];
                            $this->prepare = $this->provider->db2->prepare("SELECT player,title,x,y,z,world FROM homes WHERE player = :name AND title = :title");
                            $this->prepare->bindValue(":name", $this->username, SQLITE3_TEXT);
                            $this->prepare->bindValue(":title", $this->home_loc, SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();
                            $sql = $this->provider->fetchall();
                            if (count($sql) > 0) {
                                $sql = $sql[0];
                                if (isset($sql['world']) && Server::getInstance()->getWorldManager()->loadWorld($sql['world']) != false) {
                                    $curr_world = Server::getInstance()->getWorldManager()->getWorldByName($sql['world']);
                                    $pos = new Position((int) $sql['x'], (int) $sql['y'], (int) $sql['z'], $curr_world);
                                    $sender->teleport($pos);
                                    $this->provider->update_cooldown($this->username, time(), 'home');
                                    $sender->sendMessage($this->provider->config->get("Lang_teleport_home"));
                                    return true;
                                } else {
                                    $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Land_chunk_not_loaded"));
                                    return true;
                                }
                            } else {
                                $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_home_name"));
                                return true;
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_action_cooldown"));
                            return true;
                        }
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                }
                break;
            case 'sethome':
                if ($sender instanceof Player) {
                    if (!$sender->hasPermission("epicessentialstp.command.sethome")) {
                        $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                        return true;
                    }
                    if ((count($args) != 0) && (count($args) < 2)) {
                        $this->player_cords = array('x' => (int) $sender->getPosition()->getX(),'y' => (int) $sender->getPosition()->getY(),'z' => (int) $sender->getPosition()->getZ());
                        $this->username = $sender->getName();
                        $this->world = $sender->getWorld()->getFolderName();
                        $this->home_loc = $args[0];
                        $this->prepare = $this->provider->db2->prepare("SELECT player,title,x,y,z,world FROM homes WHERE player = :name AND title = :title");
                        $this->prepare->bindValue(":name", $this->username, SQLITE3_TEXT);
                        $this->prepare->bindValue(":title", $this->home_loc, SQLITE3_TEXT);
                        $this->result = $this->prepare->execute();
                        $sql = $this->provider->fetchall();
                        if (count($sql) > 0) {
                            $this->prepare = $this->provider->db2->prepare("UPDATE homes SET world = :world, title = :title, x = :x, y = :y, z = :z WHERE player = :name AND title = :title");
                            $this->prepare->bindValue(":name", $this->username, SQLITE3_TEXT);
                            $this->prepare->bindValue(":title", $this->home_loc, SQLITE3_TEXT);
                            $this->prepare->bindValue(":world", $this->world, SQLITE3_TEXT);
                            $this->prepare->bindValue(":x", $this->player_cords['x'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":y", $this->player_cords['y'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":z", $this->player_cords['z'], SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();
                        } else {
                            $this->prepare = $this->provider->db2->prepare("INSERT INTO homes (player, title, world, x, y, z) VALUES (:name, :title, :world, :x, :y, :z)");
                            $this->prepare->bindValue(":name", $this->username, SQLITE3_TEXT);
                            $this->prepare->bindValue(":title", $this->home_loc, SQLITE3_TEXT);
                            $this->prepare->bindValue(":world", $this->world, SQLITE3_TEXT);
                            $this->prepare->bindValue(":x", $this->player_cords['x'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":y", $this->player_cords['y'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":z", $this->player_cords['z'], SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();
                        }
                        $sender->sendMessage($this->provider->config->get("Lang_home_set")." ".TextFormat::GOLD.$args[0]);
                        return true;
                    } else {
                        $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_invalid_usage"));
                        return false;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
            case 'delhome':
                if ($sender instanceof Player) {
                    if (!$sender->hasPermission("epicessentialstp.command.delhome")) {
                        $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                        return true;
                    }
                    if ((count($args) != 0) && (count($args) < 2)) {
                        $this->username = $sender->getName();
                        $this->home_loc = $args[0];
                        $this->prepare = $this->provider->db2->prepare("SELECT * FROM homes WHERE player = :name AND title = :title");
                        $this->prepare->bindValue(":name", $this->username, SQLITE3_TEXT);
                        $this->prepare->bindValue(":title", $this->home_loc, SQLITE3_TEXT);
                        $this->result = $this->prepare->execute();
                        $sql = $this->provider->fetchall();
                        if (count($sql) > 0) {
                            $this->prepare = $this->provider->db2->prepare("DELETE FROM homes WHERE player = :name AND title = :title");
                            $this->prepare->bindValue(":name", $this->username, SQLITE3_TEXT);
                            $this->prepare->bindValue(":title", $this->home_loc, SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();
                            $sender->sendMessage($this->provider->config->get("Lang_home_delete_1")." ".TextFormat::GOLD.$this->home_loc.TextFormat::WHITE." ".$this->provider->config->get("Lang_home_delete_2"));
                            return true;
                        } else {
                            $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_home_name"));
                            return true;
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_invalid_usage"));
                        return false;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
            case 'tpa':
                if (!$sender->hasPermission("epicessentialstp.command.tpa")) {
                    $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    return true;
                }
                if ($sender instanceof Player) {
                    if ((count($args) != 0) && (count($args) < 2)) {
                        if (trim(strtolower($sender->getName())) == trim(strtolower($args[0]))) {
                            $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_teleport_self"));
                            return true;
                        }
                        $this->tp_sender  = $sender->getName();
                        $this->tp_reciver = $args[0];
                        if ($this->getServer()->getPlayerByPrefix($this->tp_reciver) instanceof Player) {
                            $this->getServer()->getPlayerByPrefix($this->tp_reciver)->sendMessage(TextFormat::GOLD . $this->tp_sender . TextFormat::WHITE . ' '.$this->provider->config->get("Lang_sent_request_you"));
                            $this->getServer()->getPlayerByPrefix($this->tp_reciver)->sendMessage($this->provider->config->get("Lang_type").' ' . TextFormat::GOLD . '/tpaccept' . TextFormat::WHITE . ' '.$this->provider->config->get("Lang_accept_request"));
                            $this->getServer()->getPlayerByPrefix($this->tp_reciver)->sendMessage($this->provider->config->get("Lang_type").' ' . TextFormat::GOLD . '/tpdecline' . TextFormat::WHITE . ' '.$this->provider->config->get("Lang_decline_request"));
                            $this->getServer()->getPlayerByPrefix($this->tp_reciver)->sendMessage($this->provider->config->get("Lang_request_expire_1").' ' . TextFormat::GOLD .$this->provider->config->get("tpa-here-cooldown").' '.$this->provider->config->get("Lang_request_expire_2") . TextFormat::WHITE . ' '.$this->provider->config->get("Lang_request_expire_3"));
                            $this->prepare = $this->provider->db2->prepare("INSERT INTO tp_requests (player, player_from, type, time, status) VALUES (:name, :name_from, :type, :time, :status)");
                            $this->prepare->bindValue(":name", trim(strtolower($this->getServer()->getPlayerByPrefix($this->tp_reciver)->getName())), SQLITE3_TEXT);
                            $this->prepare->bindValue(":name_from", trim(strtolower($this->tp_sender)), SQLITE3_TEXT);
                            $this->prepare->bindValue(":type", 'tpa', SQLITE3_TEXT);
                            $this->prepare->bindValue(":time", time(), SQLITE3_TEXT);
                            $this->prepare->bindValue(":status", 0, SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();
                            return true;
                        } else {
                            $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_player_not_online"));
                            return true;
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_invalid_usage"));
                        return false;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
            case 'tpahere':
                if (!$sender->hasPermission("epicessentialstp.command.tpahere")) {
                    $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    return true;
                }
                if ($sender instanceof Player) {
                    if ((count($args) != 0) && (count($args) < 2)) {
                        if (trim(strtolower($sender->getName())) == trim(strtolower($args[0]))) {
                            $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_teleport_self"));
                            return true;
                        }
                        $this->tp_sender = $sender->getName();
                        $this->tp_reciver = $args[0];
                        if ($this->getServer()->getPlayerByPrefix($this->tp_reciver) instanceof Player) {
                            $this->getServer()->getPlayerByPrefix($this->tp_reciver)->sendMessage(TextFormat::GOLD.$this->tp_sender.TextFormat::WHITE.' '.$this->provider->config->get("Lang_sent_request_them"));
                            $this->getServer()->getPlayerByPrefix($this->tp_reciver)->sendMessage($this->provider->config->get("Lang_type").' '.TextFormat::GOLD.'/tpaccept'.TextFormat::WHITE.' '.$this->provider->config->get("Lang_accept_request"));
                            $this->getServer()->getPlayerByPrefix($this->tp_reciver)->sendMessage($this->provider->config->get("Lang_type").' '.TextFormat::GOLD.'/tpdecline'.TextFormat::WHITE.' '.$this->provider->config->get("Lang_decline_request"));
                            $this->getServer()->getPlayerByPrefix($this->tp_reciver)->sendMessage($this->provider->config->get("Lang_request_expire_1").' '.TextFormat::GOLD.$this->provider->config->get("tpa-here-cooldown").' '.$this->provider->config->get("Lang_request_expire_2").TextFormat::WHITE.' '.$this->provider->config->get("Lang_request_expire_3"));
                            $this->prepare = $this->provider->db2->prepare("INSERT INTO tp_requests (player, player_from, type, time, status) VALUES (:name, :name_from, :type, :time, :status)");
                            $this->prepare->bindValue(":name", trim(strtolower($this->getServer()->getPlayerByPrefix($this->tp_reciver)->getName())), SQLITE3_TEXT);
                            $this->prepare->bindValue(":name_from", trim(strtolower($this->tp_sender)), SQLITE3_TEXT);
                            $this->prepare->bindValue(":type", 'tpahere', SQLITE3_TEXT);
                            $this->prepare->bindValue(":time", time(), SQLITE3_TEXT);
                            $this->prepare->bindValue(":status", 0, SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();
                            return true;
                        } else {
                            $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_player_not_online"));
                            return true;
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_invalid_usage"));
                        return false;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
            case 'tpaccept':
                if (!$sender->hasPermission("epicessentialstp.command.tpaccept")) {
                    $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    return true;
                }
                if ($sender instanceof Player) {
                    $this->prepare = $this->provider->db2->prepare("SELECT id,player, player_from, type, time, status FROM tp_requests WHERE time > :time AND player = :player AND status = 0");
                    $this->prepare->bindValue(":time", (time() - $this->provider->config->get("tpa-here-cooldown")), SQLITE3_TEXT);
                    $this->prepare->bindValue(":player", trim(strtolower($sender->getName())), SQLITE3_TEXT);
                    $this->result = $this->prepare->execute();
                    $sql = $this->provider->fetchall();
                    if (count($sql) > 0) {
                        $sql = $sql[0];
                        switch ($sql['type']) {
                          case 'tpa':
                              if ($this->getServer()->getPlayerByPrefix($sql['player_from']) instanceof Player) {
                                  $this->getServer()->getPlayerByPrefix($sql['player_from'])->teleport($sender->getPosition());
                                  $this->prepare = $this->provider->db2->prepare("UPDATE tp_requests SET status = 1 WHERE id = :id");
                                  $this->prepare->bindValue(":id", $sql['id'], SQLITE3_INTEGER);
                                  $this->result = $this->prepare->execute();
                                  return true;
                              } else {
                                  $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_player_not_online"));
                                  return true;
                              }
                          case 'tpahere':
                              if ($this->getServer()->getPlayerByPrefix($sql['player_from']) instanceof Player) {
                                  $sender->teleport($this->getServer()->getPlayerByPrefix($sql['player_from'])->getPosition());
                                  $this->prepare = $this->provider->db2->prepare("UPDATE tp_requests SET status = 1 WHERE id = :id");
                                  $this->prepare->bindValue(":id", $sql['id'], SQLITE3_INTEGER);
                                  $this->result = $this->prepare->execute();
                                  return true;
                              } else {
                                  $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_player_not_online"));
                                  return true;
                              }
                          default:
                              return false;
                      }
                    } else {
                        $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_active_request"));
                        $this->prepare = $this->provider->db2->prepare("DELETE FROM tp_requests WHERE time < :time AND player = :player AND status = 0");
                        $this->prepare->bindValue(":time", (time() - $this->provider->config->get("tpa-here-cooldown")), SQLITE3_TEXT);
                        $this->prepare->bindValue(":player", trim(strtolower($sender->getName())), SQLITE3_TEXT);
                        $this->result = $this->prepare->execute();
                        return true;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
            case 'tpdeny':
                if (!$sender->hasPermission("epicessentialstp.command.tpdeny")) {
                    $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    return true;
                }
                if ($sender instanceof Player) {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_active_request"));
                    $this->prepare = $this->provider->db2->prepare("DELETE FROM tp_requests WHERE player = :player AND status = 0");
                    $this->prepare->bindValue(":player", trim(strtolower($sender->getName())), SQLITE3_TEXT);
                    $this->result = $this->prepare->execute();
                    return true;
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
            case 'warp':
                if (!$sender->hasPermission("epicessentialstp.command.warp")) {
                    $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    return true;
                }
                if ($sender instanceof Player) {
                    if (count($args) == 0) {
                        $this->prepare = $this->provider->db2->prepare("SELECT x,y,z,world,title FROM warps");
                        $this->result = $this->prepare->execute();
                        $sql = $this->provider->fetchall();
                        $warp_list = null;
                        foreach ($sql as $ptu) {
                            $warp_list .= '['.TextFormat::GOLD.$ptu['title'].TextFormat::WHITE.'] ';
                        }
                        if ($warp_list != null) {
                            $sender->sendMessage($this->provider->config->get("Lang_warps")." ".$warp_list);
                            return true;
                        } else {
                            $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_warps"));
                            return true;
                        }
                    } else {
                        $this->prepare = $this->provider->db2->prepare("SELECT home,warp,spawn,player FROM cooldowns WHERE player =:name AND warp < :time");
                        $this->prepare->bindValue(":name", $sender->getName(), SQLITE3_TEXT);
                        $this->prepare->bindValue(":time", (time() - $this->provider->config->get("tp-warp-cooldown")), SQLITE3_TEXT);
                        $this->result = $this->prepare->execute();
                        $cool_sql = $this->provider->fetchall();
                        if (count($cool_sql) > 0) {
                            $this->warp_loc = $args[0];
                            $this->prepare = $this->provider->db2->prepare("SELECT title,x,y,z,world FROM warps WHERE title = :title");
                            $this->prepare->bindValue(":title", $this->warp_loc, SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();
                            $sql = $this->provider->fetchall();
                            if (count($sql) > 0) {
                                $sql = $sql[0];
                                if (isset($sql['world'])) {
                                    if (Server::getInstance()->getWorldManager()->getWorldByName($sql['world']) != false) {
                                        $curr_world = Server::getInstance()->getWorldManager()->getWorldByName($sql['world']);
                                        $pos = new Position((int) $sql['x'], (int) $sql['y'], (int) $sql['z'], $curr_world);
                                        $sender->sendMessage($this->provider->config->get("Lang_warp_to") . " " . TextFormat::GOLD . $sql['title']);
                                        $sender->teleport($pos);
                                        $this->provider->update_cooldown($sender->getName(), time(), 'warp');
                                        return true;
                                    } else {
                                        $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Land_chunk_not_loaded"));
                                        return true;
                                    }
                                }
                            } else {
                                $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_warp_listed"));
                                return true;
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_action_cooldown"));
                            return true;
                        }
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
            case 'setwarp':
                if (!$sender->hasPermission("epicessentialstp.command.setwarp")) {
                    $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    return true;
                }
                if ($sender instanceof Player) {
                    if ((count($args) != 0) && (count($args) < 2)) {
                        $this->player_cords = array('x' => (int) $sender->getPosition()->getX(),'y' => (int) $sender->getPosition()->getY(),'z' => (int) $sender->getPosition()->getZ());
                        $this->world = $sender->getWorld()->getFolderName();
                        $this->warp_loc = $args[0];
                        $this->prepare = $this->provider->db2->prepare("SELECT title,x,y,z,world FROM warps WHERE title = :title");
                        $this->prepare->bindValue(":title", $this->warp_loc, SQLITE3_TEXT);
                        $this->result = $this->prepare->execute();
                        $sql = $this->provider->fetchall();
                        if (count($sql) > 0) {
                            $sql = $sql[0];
                            $this->prepare = $this->provider->db2->prepare("UPDATE warps SET world = :world, title = :title, x = :x, y = :y, z = :z WHERE title = :title");
                            $this->prepare->bindValue(":title", $this->warp_loc, SQLITE3_TEXT);
                            $this->prepare->bindValue(":world", $this->world, SQLITE3_TEXT);
                            $this->prepare->bindValue(":x", $this->player_cords['x'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":y", $this->player_cords['y'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":z", $this->player_cords['z'], SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();
                        } else {
                            $this->prepare = $this->provider->db2->prepare("INSERT INTO warps (title, world, x, y, z) VALUES (:title, :world, :x, :y, :z)");
                            $this->prepare->bindValue(":title", $this->warp_loc, SQLITE3_TEXT);
                            $this->prepare->bindValue(":world", $this->world, SQLITE3_TEXT);
                            $this->prepare->bindValue(":x", $this->player_cords['x'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":y", $this->player_cords['y'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":z", $this->player_cords['z'], SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();
                        }
                        $sender->sendMessage($this->provider->config->get("Lang_warp_set")." ".TextFormat::GOLD.$args[0]);
                        return true;
                    } else {
                        $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_invalid_usage"));
                        return false;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
            case 'delwarp':
                if (!$sender->hasPermission("epicessentialstp.command.delwarp")) {
                    $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    return true;
                }
                if ($sender instanceof Player) {
                    if ((count($args) != 0) && (count($args) < 2)) {
                        $this->warp_loc = $args[0];
                        $this->prepare = $this->provider->db2->prepare("SELECT * FROM warps WHERE title = :title");
                        $this->prepare->bindValue(":title", $this->warp_loc, SQLITE3_TEXT);
                        $this->result = $this->prepare->execute();
                        $sql = $this->provider->fetchall();
                        if (count($sql) > 0) {
                            $this->prepare = $this->provider->db2->prepare("DELETE FROM warps WHERE title = :title");
                            $this->prepare->bindValue(":title", $this->warp_loc, SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();
                            $sender->sendMessage($this->provider->config->get("Lang_warp_delete_1")." ".TextFormat::GOLD.$this->warp_loc.TextFormat::WHITE." ".$this->provider->config->get("Lang_warp_delete_2"));
                            return true;
                        } else {
                            $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_warps_name"));
                            return true;
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_invalid_usage"));
                        return false;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
            case 'wild':
                if (!$sender->hasPermission("epicessentialstp.command.wild")) {
                    $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    return true;
                }
                if ($sender instanceof Player) {
                    $this->world = $sender->getWorld()->getFolderName();
                    foreach ($this->getServer()->getWorldManager()->getWorlds() as $aval_world => $curr_world) {
                        if ($this->world == $curr_world->getFolderName()) {
                            $x = rand(
                                '-'.$this->provider->config->get("wild-MaxX"),
                                $this->provider->config->get("wild-MaxX")
                            );
                            $y = rand(70, 100);
                            $z = rand(
                                '-'.$this->provider->config->get("wild-MaxY"),
                                $this->provider->config->get("wild-MaxY")
                            );
                            $sender->getWorld()->loadChunk($x, $z);
                            if (!$sender->getWorld()->isChunkLoaded($x, $z)) {
                                $sender->sendMessage($this->provider->config->get("Land_chunk_not_loaded"));
                                return true;
                            }
                            $pos = $sender->getWorld()->getSafeSpawn(new Vector3($x, $y, $z));
                            $pos->getWorld()->getChunk($x, $z);
                            $pos = $pos->getWorld()->getSafeSpawn(new Vector3($x, rand(4, 100), $z));
                            $sender->teleport($pos->getWorld()->getSafeSpawn(new Vector3($x, rand(4, 100), $z)));
                            $sender->sendMessage($this->provider->config->get("Lang_teleport_wild"));
                            return true;
                        }
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
                break;
            case 'back':
                if (!$sender->hasPermission("epicessentialstp.command.back")) {
                    $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    return true;
                }
                if ($sender instanceof Player) {
                    if (isset($this->death_loc[$sender->getName()]) && $this->death_loc[$sender->getName()] instanceof Position) {
                        $sender->teleport($this->death_loc[$sender->getName()]);
                        $sender->sendMessage($this->provider->config->get("Lang_teleport_death"));
                        unset($this->death_loc[$sender->getName()]);
                        return true;
                    } else {
                        $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_death"));
                        return true;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
                break;
            case 'spawn':
                if (!$sender->hasPermission("epicessentialstp.command.spawn")) {
                    $sender->sendMessage(TextFormat::RED . $this->provider->config->get("Lang_no_permissions"));
                    return true;
                }
                if ($sender instanceof Player) {
                    $this->prepare = $this->provider->db2->prepare("SELECT home,warp,spawn,player FROM cooldowns WHERE player =:name AND spawn < :time");
                    $this->prepare->bindValue(":name", $sender->getName(), SQLITE3_TEXT);
                    $this->prepare->bindValue(":time", (time() - $this->provider->config->get("tp-spawn-cooldown")), SQLITE3_TEXT);
                    $this->result = $this->prepare->execute();
                    $cool_sql = $this->provider->fetchall();
                    if (count($cool_sql) > 0) {
                        $this->world = $sender->getWorld()->getFolderName();
                        $this->prepare = $this->provider->db2->prepare("SELECT x,y,z,world FROM spawns WHERE world = :world");
                        $this->prepare->bindValue(":world", $this->world, SQLITE3_TEXT);
                        $this->result = $this->prepare->execute();
                        $sql = $this->provider->fetchall();
                        if (count($sql) > 0) {
                            $sql = $sql[0];
                            foreach ($this->getServer()->getWorldManager()->getWorlds() as $aval_world => $curr_world) {
                                if ($sql['world'] == $curr_world->getFolderName()) {
                                    $pos = new Position((int) $sql['x'], (int) $sql['y'], (int) $sql['z'], $curr_world);

                                    $sender->teleport($pos);
                                    $this->provider->update_cooldown($sender->getName(), time(), 'spawn');
                                    $sender->sendMessage($this->provider->config->get("Lang_teleport_spawn"));
                                    return true;
                                }
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_no_spawn_set"));
                            $sender->teleport($sender->getWorld()->getSpawnLocation());
                            $this->provider->update_cooldown($sender->getName(), time(), 'spawn');
                            $sender->sendMessage($this->provider->config->get("Lang_teleport_spawn_original"));
                            return true;
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_action_cooldown"));
                        return true;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
                break;
            case 'setspawn':
                if (!$sender->hasPermission("epicessentialstp.command.setspawn")) {
                    $sender->sendMessage(TextFormat::RED .$this->provider->config->get("Lang_no_permissions"));
                    return true;
                }
                if ($sender instanceof Player) {
                    if (count($args) == 0) {
                        $this->player_cords = array('x' => (int) $sender->getPosition()->getX(),'y' => (int) $sender->getPosition()->getY(),'z' => (int) $sender->getPosition()->getZ());
                        $this->world = $sender->getWorld()->getFolderName();
                        $this->prepare = $this->provider->db2->prepare("SELECT x,y,z,world FROM spawns WHERE world = :world");
                        $this->prepare->bindValue(":world", $this->world, SQLITE3_TEXT);
                        $this->result = $this->prepare->execute();
                        $sql = $this->provider->fetchall();
                        if (count($sql) > 0) {
                            $this->prepare = $this->provider->db2->prepare("UPDATE spawns SET world = :world, x = :x, y = :y, z = :z WHERE world = :world");
                            $this->prepare->bindValue(":world", $this->world, SQLITE3_TEXT);
                            $this->prepare->bindValue(":x", $this->player_cords['x'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":y", $this->player_cords['y'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":z", $this->player_cords['z'], SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();
                        } else {
                            $this->prepare = $this->provider->db2->prepare("INSERT INTO spawns (world, x, y, z) VALUES (:world, :x, :y, :z)");
                            $this->prepare->bindValue(":world", $this->world, SQLITE3_TEXT);
                            $this->prepare->bindValue(":x", $this->player_cords['x'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":y", $this->player_cords['y'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":z", $this->player_cords['z'], SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();
                        }

                        $sender->sendMessage($this->provider->config->get("Lang_spawn_set"));
                        return true;
                    } else {
                        $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_invalid_usage"));
                        return false;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED.$this->provider->config->get("Lang_command_only_use_ingame"));
                    return true;
                }
            default:
                return false;
            }
        return true;
    }
}
