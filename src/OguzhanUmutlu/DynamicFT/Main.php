<?php

namespace OguzhanUmutlu\DynamicFT;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\level\Position;
use pocketmine\event\Listener;

class Main extends PluginBase implements Listener {
    public $ftEntities = [];
    public $ftConfig;
    public $fts = [];
    public $config;
    public $commands = [];
    static $instance;

    public function onEnable(): void {
        self::$instance = $this;
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, ["checkSeconds" => 1, "modules" => ["EconomyAPI" => false, "FactionsPro" => false]]);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->ftConfig = new Config($this->getDataFolder() . "fts.yml", Config::YAML, ["data" => []]);
        $this->getScheduler()->scheduleRepeatingTask(new FtTask($this), intval((float)$this->config->getNested("checkSeconds") * 20));
        foreach($this->ftConfig->getNested("data") as $ft) {
            $this->registerFT($ft["text"], new Position($ft["x"], $ft["y"], $ft["z"], $this->getServer()->getLevelByName($ft["level"])), false);
        }
    }

    static function getInstance() {
        return self::$instance;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if(!isset($args[0])) $args[0] = "";
        if($command->getName() != "dynamicft") return true;
        $args[0] = strtolower($args[0]);
        if(!isset($this->commands[$sender->getName()])) {
            $this->commands[$sender->getName()] = [];
        }
        switch($args[0]) {
            case "spawn":
            case "create":
            case "s":
            case "c":
                if(!$sender->hasPermission($command->getPermission().".create")) {
                    $sender->sendMessage("§c> You don't have permission to create floating texts.");
                    return true;
                }
                $sender->sendMessage("§e> Type the text of the floating text. To cancel type \$cancel to chat.");
                $this->commands[$sender->getName()]["create"] = true;
                break;
            case "edit":
            case "e":
                if(!$sender->hasPermission($command->getPermission().".edit")) {
                    $sender->sendMessage("§c> You don't have permission to edit floating texts.");
                    return true;
                }
                if(!isset($args[1])) $args[1] = "";
                switch($args[1]) {
                    case "tphere":
                    case "tpme":
                        if(!$sender->hasPermission($command->getPermission().".edit.tphere")) {
                            $sender->sendMessage("§c> You don't have permission to edit floating texts' position.");
                            return true;
                        }
                        if(!isset($args[2])) {
                            $sender->sendMessage("§c> Usage: /dft edit tpme [ id ]");
                            return true;
                        }
                        if(!$this->getRegisteredFt(intval($args[2]))) {
                            $sender->sendMessage("§c> Floating text not found.");
                            return true;
                        }
                        $this->updateRegisteredFt(intval($args[2]), "x", intval($sender->getX()));
                        $this->updateRegisteredFt(intval($args[2]), "y", intval($sender->getY()));
                        $this->updateRegisteredFt(intval($args[2]), "z", intval($sender->getZ()));
                        $sender->sendMessage("§a> Teleported floating text to you.");
                        break;
                    case "tpto":
                        if(!$sender->hasPermission($command->getPermission().".edit.tpto")) {
                            $sender->sendMessage("§c> You don't have permission to teleporting floating texts.");
                            return true;
                        }
                        if(!isset($args[2])) {
                            $sender->sendMessage("§c> Usage: /dft edit tpto [ id ]");
                            return true;
                        }
                        if(!$this->getRegisteredFt(intval($args[2]))) {
                            $sender->sendMessage("§c> Floating text not found.");
                            return true;
                        }
                        $sender->teleport(new Position($this->getRegisteredFt(intval($args[2]))["x"], $this->getRegisteredFt(intval($args[2]))["y"], $this->getRegisteredFt(intval($args[2]))["z"], $this->getServer()->getLevelByName($this->getRegisteredFt(intval($args[2]))["level"])));
                        $sender->sendMessage("§a> Teleported you to floating text.");
                        break;
                    case "text":
                        if(!$sender->hasPermission($command->getPermission().".edit.text")) {
                            $sender->sendMessage("§c> You don't have permission to teleporting floating texts.");
                            return true;
                        }
                        if(!isset($args[2])) {
                            $sender->sendMessage("§c> Usage: /dft edit text [ id ]");
                            return true;
                        }
                        if(!$this->getRegisteredFt(intval($args[2]))) {
                            $sender->sendMessage("§c> Floating text not found.");
                            return true;
                        }
                        $sender->sendMessage("§e> Type new text of floating text to chat. To cancel type \$cancel to chat.");
                        $this->commands[$sender->getName()]["editText"] = $this->getRegisteredFt(intval($args[2]));
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
                if (!$this->getRegisteredFt(intval($args[1]))) {
                    $sender->sendMessage("§c> Floating text not found.");
                    return true;
                }
                $this->unregisterFT(intval($args[1]));
                $sender->sendMessage("§a> Floating text removed.");
                break;
            case "listids":
                $list = array_chunk($this->fts, 5);
                if(!isset($args[1])) {
                    $args[1] = "1";
                }
                if(!is_numeric($args[1])) {
                    $sender->sendMessage("§c> Usage: /dft listids [ page (1/".count($list)."): number ]");
                    return true;
                }
                $sender->sendMessage("§e> Floating texts, Page ".$args[1]."/".count($list));
                foreach($list[intval($args[1])-1] as $item) {
                    $sender->sendMessage("§a> ID: " . $item["id"] . ", Text: " . $item["text"] . ", X: " . $item["x"] . ", Y: " . $item["y"] . ", Z: " . $item["z"] . ", Level: " . $item["level"]);
                }
                $sender->sendMessage("§e> Floating texts, Page ".$args[1]."/".count($list));
                break;
            default:
                $sender->sendMessage("§c> Usage: /dft [ create, edit, remove, listids ]");
                break;
        }
        return true;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        foreach($this->fts as $ft) {
            $this->spawnFT($ft["id"], $player);
        }
    }
    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        foreach($this->ftEntities as $ft) {
            if($ft["player"]->getName() == $player->getName()) {
                $this->removeFT($ft["id"]);
            }
        }
    }

    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $message = $event->getMessage();
        if(!isset($this->commands[$player->getName()])) {
            return;
        }
        if(isset($this->commands[$player->getName()]["editText"]) && $this->commands[$player->getName()]["editText"]) {
            $event->setCancelled(true);
            if($message == "\$cancel") {
                $this->commands[$player->getName()]["editText"] = false;
                $player->sendMessage("§a> Action cancelled.");
                return;
            }
            $this->updateRegisteredFt($this->commands[$player->getName()]["editText"]["id"], "text", $message);
            $player->sendMessage("§a> Floating text's text updated.");
        } else if(isset($this->commands[$player->getName()]["create"]) && $this->commands[$player->getName()]["create"]) {
            $event->setCancelled(true);
            if($message == "\$cancel") {
                $this->commands[$player->getName()]["create"] = false;
                $player->sendMessage("§a> Action cancelled.");
                return;
            }
            $this->registerFT($message, $player->getPosition());
            $player->sendMessage("§a> Floating text created.");
        }
    }

    public function registerFT(string $text, Position $pos, bool $addToData = true): int {
        if(!$this->getServer()->isLevelGenerated($pos->getLevel()->getName())) {
            return -1;
        }
        if(!$this->getServer()->isLevelLoaded($pos->getLevel()->getName())) {
            $this->getServer()->loadLevel($pos->getLevel()->getName());
        }
        if(!$pos->getLevel()->isChunkLoaded($pos->getX() >> 4, $pos->getZ() >> 4)) {
            $pos->getLevel()->loadChunk($pos->getX() >> 4, $pos->getZ() >> 4);
        }
        $ft = ["text" => $text, "x" => $pos->getX(), "y" => $pos->getY(), "z" => $pos->getZ(), "level" => $pos->getLevel()->getFolderName()];
        $a = $this->ftConfig->getNested("data");
        array_push($a, $ft);

        if($addToData) {
            $this->ftConfig->setNested("data", $a);
            $this->ftConfig->save();
            $this->ftConfig->reload();
        }
        $ft["id"] = isset($this->fts[0]) ? $this->fts[count($this->fts)-1]["id"]+1 : 0;
        array_push($this->fts, $ft);
        foreach($this->getServer()->getOnlinePlayers() as $p) {
            $this->spawnFT($ft["id"], $p);
        }
        return $ft["id"];
    }

    public function unregisterFT(int $typeId): void {
        if(!$this->getRegisteredFt($typeId)) {
            return;
        }
        $a = $this->ftConfig->getNested("data");
        $ft = $this->getRegisteredFt($typeId);
        unset($ft["id"]);
        unset($a[array_search($ft, $a)]);
        $this->ftConfig->setNested("data", $a);
        $this->ftConfig->save();
        $this->ftConfig->reload();
        $ft["id"] = $typeId;
        unset($this->fts[array_search($ft, $this->fts)]);
    }

    public function updateRegisteredFt(int $typeId, string $data, $property): void {
        if(!$this->getRegisteredFt($typeId) || $data == "id") {
            return;
        }
        $this->unregisterFT($typeId);
        $ft = $this->getRegisteredFt($typeId);
        $ft[$data] = $property;
        $this->registerFT($ft["text"], new Position($ft["x"], $ft["y"], $ft["z"], $this->getServer()->getLevelByName($ft["level"])));
        foreach($this->ftEntities as $x) {
            $this->removeFT($x["id"]);
            $this->spawnFT($x["creationId"], $x["player"]);
        }
    }

    public function getRegisteredFt(int $typeId): ?array {
        $result = null;
        foreach($this->fts as $n) {
            if($n["id"] == $typeId) {
                $result = $n;
            }
        }
        return $result;
    }

    public function getRegisteredFtIndex(int $typeId): ?int {
        $result = null;
        foreach($this->fts as $i => $n) {
            if($n["id"] == $typeId) {
                $result = $i;
            }
        }
        return $result;
    }

    public function spawnFT(int $typeId, Player $player): void {
        $ft = $this->getRegisteredFt($typeId);
        if(!$ft || !$this->getServer()->getLevelByName($ft["level"])) {
            return;
        }
        if(!$this->getServer()->isLevelGenerated($ft["level"])) {
            return;
        }
        if(!$this->getServer()->isLevelLoaded($ft["level"])) {
            $this->getServer()->loadLevel($ft["level"]);
        }
        $pos = new Position($ft["x"], $ft["y"], $ft["z"], $this->getServer()->getLevelByName($ft["level"]));
        if(!$pos->getLevel()->isChunkLoaded($pos->getX() >> 4, $pos->getZ() >> 4)) {
            $pos->getLevel()->loadChunk($pos->getX() >> 4, $pos->getZ() >> 4);
        }
        $text = $ft["text"];
        $particle = new FloatingTextParticle($pos, "", $text);
        $pos->getLevel()->addParticle($particle, [$player]);
        $this->ftEntities[] = ["player" => $player, "particle" => $particle, "id" => isset($this->ftEntities[0]) ? $this->ftEntities[count($this->ftEntities)-1]["id"]+1 : 0, "creationId" => $typeId];
    }

    public function removeFT(int $spawnedId): void {
        $ft = $this->getSpawnedFT($spawnedId);
        if(!$ft) {
            return;
        }
        $ft["particle"]->setInvisible(true);
        unset($this->ftEntities[$this->getSpawnedFTIndex($spawnedId)]);
    }

    public function updateFt(int $spawnedId, string $data, $property): void {
        $ft = $this->getSpawnedFT($spawnedId);
        if(!$ft || $data == "id") {
            return;
        }
        $index = $this->getSpawnedFTIndex($spawnedId);
        $ftt = $this->getRegisteredFt($ft["creationId"]);
        $ft[$data] = $property;
        $this->getServer()->getLevelByName($ftt["level"])->addParticle($ft["particle"], [$ft["player"]]);
        $this->ftEntities[$index] = $ft;
    }

    public function getSpawnedFT(int $spawnedId): ?array {
        $result = null;
        foreach($this->ftEntities as $n) {
            if($n["id"] == $spawnedId) {
                $result = $n;
            }
        }
        return $result;
    }

    public function getSpawnedFTIndex(int $spawnedId): ?int {
        $result = null;
        foreach($this->ftEntities as $i => $n) {
            if($n["id"] == $spawnedId) {
                $result = $i;
            }
        }
        return $result;
    }
}
