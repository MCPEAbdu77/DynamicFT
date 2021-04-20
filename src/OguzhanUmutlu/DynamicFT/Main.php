<?php

namespace OguzhanUmutlu\DynamicFT;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\level\Position;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\level\particle\FloatingTextParticle;

class Main extends PluginBase implements Listener
{
    public $ftEntities = [];
    public $ftConfig;
    public $fts = [];
    public $config;
    public $commands = [];
    private $customTags = [];
    static $instance;

    public function onEnable(): void
    {
        self::$instance = $this;
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, ["checkSeconds" => 1, "modules" => ["EconomyAPI" => false, "FactionsPro" => false]]);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->ftConfig = new Config($this->getDataFolder() . "fts.yml", Config::YAML, []);
        $this->fts = $this->ftConfig->getAll();
        if(isset($this->fts["data"])) {
            $this->fts = $this->fts["data"];
            $this->ftConfig->setAll($this->fts);
            $this->ftConfig->save();
            $this->ftConfig->reload();
            $this->getLogger()->notice("Config version successfully updated! ;)");
        }
        $this->getScheduler()->scheduleRepeatingTask(new FtTask($this), (int)((float)$this->config->getNested("checkSeconds") * 20));
    }

    static function getInstance()
    {
        return self::$instance;
    }

    public function getCustomTags(): array
    {
        return array_map(function ($n) {
            return ["tag" => $n["tag"], "function" => $n["function"]];
        }, $this->customTags);
    }

    public function addCustomTag(string $tag, callable $function): bool
    {
        if (isset($this->customTags[$tag])) return false;
        $this->customTags[$tag] = ["tag" => $tag, "function" => $function];
        return true;
    }

    public function changeCustomTag(string $tag, callable $function): bool
    {
        if (!isset($this->customTags[$tag])) return false;
        $this->customTags[$tag] = ["tag" => $tag, "function" => $function];
        return true;
    }

    public function deleteCustomTag(string $tag): bool
    {
        if (!isset($this->customTags[$tag])) return false;
        unset($this->customTags[$tag]);
        return true;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if (!isset($args[0])) $args[0] = "";
        $args[0] = strtolower($args[0]);
        if (!isset($this->commands[$sender->getName()])) {
            $this->commands[$sender->getName()] = [];
        }
        switch ($args[0]) {
            case "spawn":
            case "create":
            case "s":
            case "c":
                if (!$sender->hasPermission($command->getPermission() . ".create")) {
                    $sender->sendMessage("§c> You don't have permission to create floating texts.");
                    return true;
                }
                $sender->sendMessage("§e> Type the text of the floating text. To cancel type \$cancel to chat.");
                $this->commands[$sender->getName()]["create"] = true;
                break;
            case "edit":
            case "e":
                if (!$sender->hasPermission($command->getPermission() . ".edit")) {
                    $sender->sendMessage("§c> You don't have permission to edit floating texts.");
                    return true;
                }
                if (!isset($args[1])) $args[1] = "";
                switch ($args[1]) {
                    case "tphere":
                    case "tpme":
                        if (!$sender instanceof Player) {
                            $sender->sendMessage("§c> Please use this command in-game.");
                            return true;
                        }
                        if (!$sender->hasPermission($command->getPermission() . ".edit.tphere")) {
                            $sender->sendMessage("§c> You don't have permission to edit floating texts' position.");
                            return true;
                        }
                        if (!isset($args[2])) {
                            $sender->sendMessage("§c> Usage: /dft edit tpme [ id ]");
                            return true;
                        }
                        if (!isset($this->fts[(int)$args[2]])) {
                            $sender->sendMessage("§c> Floating text not found.");
                            return true;
                        }
                        $this->fts[(int)$args[2]]["x"] = (float)$sender->getX();
                        $this->fts[(int)$args[2]]["y"] = (float)$sender->getY();
                        $this->fts[(int)$args[2]]["z"] = (float)$sender->getZ();
                        $this->fts[(int)$args[2]]["level"] = (string)$sender->getLevel()->getFolderName();
                        foreach($this->ftEntities as $i => $ftEntity) {
                            $this->removeFt($i);
                            $this->spawnFt((int)$args[2], $ftEntity["player"]);
                        }
                        $this->ftConfig->setAll($this->fts);
                        $this->ftConfig->save();
                        $this->ftConfig->reload();
                        $sender->sendMessage("§a> Teleported floating text to you.");
                        break;
                    case "tpto":
                        if (!$sender instanceof Player) {
                            $sender->sendMessage("§c> Use this command in-game.");
                            return true;
                        }
                        if (!$sender->hasPermission($command->getPermission() . ".edit.tpto")) {
                            $sender->sendMessage("§c> You don't have permission to teleporting floating texts.");
                            return true;
                        }
                        if (!isset($args[2])) {
                            $sender->sendMessage("§c> Usage: /dft edit tpto [ id ]");
                            return true;
                        }
                        if (!isset($this->fts[(int)$args[2]])) {
                            $sender->sendMessage("§c> Floating text not found.");
                            return true;
                        }
                        $rft = $this->fts[(int)$args[2]];
                        $sender->teleport(new Position($rft["x"], $rft["y"], $rft["z"], $this->getServer()->getLevelByName($rft["level"])));
                        $sender->sendMessage("§a> Teleported you to floating text.");
                        break;
                    case "text":
                        if (!$sender->hasPermission($command->getPermission() . ".edit.text")) {
                            $sender->sendMessage("§c> You don't have permission to teleporting floating texts.");
                            return true;
                        }
                        if (!isset($args[2])) {
                            $sender->sendMessage("§c> Usage: /dft edit text [ id ]");
                            return true;
                        }
                        if (!isset($this->fts[(int)$args[2]])) {
                            $sender->sendMessage("§c> Floating text not found.");
                            return true;
                        }
                        $sender->sendMessage("§e> Type new text of floating text to chat. To cancel type \$cancel to chat.");
                        $this->commands[$sender->getName()]["editText"] = (int)$args[2];
                        break;
                    default:
                        $sender->sendMessage("§c> Usage: /dft edit [ tphere, tpto, text ]");
                        break;
                }
                break;
            case "remove":
            case "delete":
            case "r":
            case "d":
                if (!$sender->hasPermission($command->getPermission() . ".remove")) {
                    $sender->sendMessage("§c> You don't have permission to remove floating texts.");
                    return true;
                }
                if (!isset($args[1]) || !is_numeric($args[1])) {
                    $sender->sendMessage("§c> Usage: /dft remove [ id ]");
                    return true;
                }
                if (!isset($this->fts[(int)$args[1]])) {
                    $sender->sendMessage("§c> Floating text not found.");
                    return true;
                }
                unset($this->fts[(int)$args[1]]);
                foreach($this->ftEntities as $i => $x) {
                    if($x["id"] == (int)$args[1]) {
                        $this->removeFt($i);
                    }
                }
                $this->ftConfig->setAll($this->fts);
                $this->ftConfig->save();
                $this->ftConfig->reload();
                $sender->sendMessage("§a> Floating text removed.");
                break;
            case "list":
            case "listids":
                $list = array_chunk($this->fts, 5);
                if (count($list) < 1) {
                    $sender->sendMessage("§c> There is no dynamic floating text.");
                    return true;
                }
                if (!isset($args[1])) {
                    $args[1] = "1";
                }
                if (!is_numeric($args[1])) {
                    $sender->sendMessage("§c> Usage: /dft listids [ page (1/" . count($list) . "): number ]");
                    return true;
                }
                $sender->sendMessage("§e> Floating texts, Page " . $args[1] . "/" . count($list));
                foreach ($list[(int)$args[1] - 1] as $id => $item) {
                    $sender->sendMessage("§a> ID: " . $id . ", Text: " . $item["text"] . ", X: " . $item["x"] . ", Y: " . $item["y"] . ", Z: " . $item["z"] . ", Level: " . $item["level"]);
                }
                $sender->sendMessage("§e> Floating texts, Page " . $args[1] . "/" . count($list));
                break;
            default:
                $sender->sendMessage("§c> Usage: /dft [ create, edit, remove, listids ]");
                break;
        }
        return true;
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        foreach ($this->fts as $id => $ft) {
            $this->spawnFt($id, $player);
        }
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        foreach ($this->ftEntities as $ft) {
            if ($ft["player"]->getName() == $player->getName()) {
                $this->removeFt($ft["id"]);
            }
        }
    }

    public function onChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        $message = $event->getMessage();
        if (!isset($this->commands[$player->getName()])) {
            return;
        }
        if (isset($this->commands[$player->getName()]["editText"]) && !is_null($this->commands[$player->getName()]["editText"])) {
            $event->setCancelled(true);
            if ($message == "\$cancel") {
                $this->commands[$player->getName()]["editText"] = false;
                $player->sendMessage("§a> Action cancelled.");
                return;
            }
            if (!isset($this->commands[$player->getName()]["editText"])) {
                $this->commands[$player->getName()]["editText"] = false;
                $player->sendMessage("§c> DFT not found.");
                return;
            }
            $this->fts[$this->commands[$player->getName()]["editText"]]["text"] = $message;
            foreach($this->ftEntities as $ftEntity) {
                if($ftEntity["id"] == $this->commands[$player->getName()]["editText"]) {
                    $this->removeFt($ftEntity["id"]);
                    $this->spawnFt($ftEntity["id"], $ftEntity["player"]);
                }
            }
            $this->ftConfig->setAll($this->fts);
            $this->ftConfig->save();
            $this->ftConfig->reload();
            $this->commands[$player->getName()]["editText"] = false;
            $player->sendMessage("§a> Floating text's text updated.");
        } else if (isset($this->commands[$player->getName()]["create"]) && $this->commands[$player->getName()]["create"]) {
            $event->setCancelled(true);
            if ($message == "\$cancel") {
                $this->commands[$player->getName()]["create"] = false;
                $player->sendMessage("§a> Action cancelled.");
                return;
            }
            $this->commands[$player->getName()]["create"] = false;
            $newFt = [
                "x" => $player->getX(),
                "y" => $player->getY(),
                "z" => $player->getZ(),
                "level" => $player->getLevel()->getFolderName(),
                "text" => $message
            ];
            $this->fts[] = $newFt;
            foreach($this->getServer()->getOnlinePlayers() as $player) {
                $this->spawnFt(array_search($newFt, $this->fts), $player);
            }
            $this->ftConfig->setAll($this->fts);
            $this->ftConfig->save();
            $this->ftConfig->reload();
            $player->sendMessage("§a> Floating text created.");
        }
    }

    public function spawnFt(int $id, Player $player): void
    {
        if (!isset($this->fts[$id])) return;
        $ft = $this->fts[$id];
        if (!$this->getServer()->isLevelGenerated($ft["level"])) {
            return;
        }
        if (!$this->getServer()->isLevelLoaded($ft["level"])) {
            $this->getServer()->loadLevel($ft["level"]);
        }
        $pos = new Position($ft["x"], $ft["y"], $ft["z"], $this->getServer()->getLevelByName($ft["level"]));
        if (!$pos->getLevel()->isChunkLoaded($pos->getX() >> 4, $pos->getZ() >> 4)) {
            $pos->getLevel()->loadChunk($pos->getX() >> 4, $pos->getZ() >> 4);
        }
        $particle = new FloatingTextParticle($pos, "", $ft["text"]);
        $pos->getLevel()->addParticle($particle, [$player]);
        $this->ftEntities[] = ["player" => $player, "particle" => $particle, "id" => $id];
    }

    public function removeFt(int $id): void
    {
        if (!isset($this->ftEntities[$id])) return;
        $ft = $this->ftEntities[$id];
        if(!isset($this->fts[$ft["id"]])) return;
        $ft["particle"]->setInvisible(true);
        $ftt = $this->fts[$ft["id"]];
        $pos = new Position($ftt["x"], $ftt["y"], $ftt["z"], $this->getServer()->getLevelByName($ftt["level"]));
        if ($this->getServer()->isLevelGenerated($ftt["level"])) {
            if (!$this->getServer()->isLevelLoaded($ftt["level"])) {
                $this->getServer()->loadLevel($ftt["level"]);
            }
            if (!$pos->getLevel()->isChunkLoaded($pos->getX() >> 4, $pos->getZ() >> 4)) {
                $pos->getLevel()->loadChunk($pos->getX() >> 4, $pos->getZ() >> 4);
            }
            $this->updateFt($id, "particle", $ft["particle"]);
        }
        unset($this->ftEntities[$id]);
    }

    public function updateFt(int $id, string $data, $property): void
    {
        if (!isset($this->ftEntities[$id]) || $data == "id" || !isset($this->fts[$this->ftEntities[$id]["id"]])) {
            return;
        }
        $ft = $this->ftEntities[$id];
        $ft[$data] = $property;
        $ftt = $this->fts[$this->ftEntities[$id]["id"]];
        $this->getServer()->getLevelByName($ftt["level"])->addParticle($ft["particle"], [$ft["player"]]);
        $this->ftEntities[$id] = $ft;
    }

    public function getAllSpawnedFts(): array
    {
        return $this->ftEntities;
    }
}
