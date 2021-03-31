<?php

namespace OguzhanUmutlu\DynamicFT;

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
    static $instance;

    public function onEnable(): void {
        self::$instance = $this;
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, ["checkSeconds" => 1, "modules" => ["EconomyAPI" => false, "FactionsPro" => false]]);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->ftConfig = new Config($this->getDataFolder() . "fts.yml", Config::YAML, ["data" => []]);
        $this->getScheduler()->scheduleRepeatingTask(new FtTask($this), intval((float)$this->config->getNested("checkSeconds") * 20));
        foreach($this->ftConfig->getNested("data") as $ft) {
            var_dump($this->registerFT($ft["text"], new Position($ft["x"], $ft["y"], $ft["z"], $this->getServer()->getLevelByName($ft["level"])), false));
        }
    }

    static function getInstance() {
        return self::$instance;
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

    public function registerFT(string $text, Position $pos, bool $addToData = false): int {
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
    }

    public function getRegisteredFt(int $typeId): ?array {
        $results = array_filter($this->fts, function($n) use ($typeId){return $n["id"] == $typeId;});
        return isset($results[0]) ? $results[0] : null;
    }

    public function spawnFT(int $typeId, Player $player): void {
        $ft = $this->getRegisteredFt($typeId);
        if(!$ft || !$this->getServer()->getLevelByName($ft["level"])) {
            return;
        }
        $pos = new Position($ft["x"], $ft["y"], $ft["z"], $this->getServer()->getLevelByName($ft["level"]));
        $text = $ft["text"];
        $particle = new FloatingTextParticle($pos, $text);
        $pos->getLevel()->addParticle($particle, [$player]);
        array_push($this->ftEntities, ["player" => $player, "particle" => $particle, "id" => isset($this->ftEntities[0]) ? $this->ftEntities[count($this->ftEntities)-1]["id"]+1 : 0, "creationId" => $typeId]);
    }

    public function removeFT(int $spawnedId): void {
        $ft = $this->getSpawnedFT($spawnedId);
        if(!$ft) {
            return;
        }
        $ft["particle"]->setInvisible(true);
        unset($this->ftEntities[array_search($ft, $this->ftEntities)]);
    }

    public function updateFt(int $spawnedId, string $data, $property): void {
        $ft = $this->getSpawnedFT($spawnedId);
        if(!$ft || $data == "id") {
            return;
        }
        $this->removeFT($spawnedId);
        $ft[$data] = $property;
        $this->spawnFT($ft["creationId"], $ft["player"]);
    }

    public function getSpawnedFT(int $spawnedId): ?array {
        $results = array_filter($this->ftEntities, function($n) use ($spawnedId){return $n["id"] == $spawnedId;});
        return isset($results[0]) ? $results[0] : null;
    }
}