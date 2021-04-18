<?php


namespace OguzhanUmutlu\DynamicFT;

use pocketmine\level\Position;
use pocketmine\scheduler\Task;
use onebone\economyapi\EconomyAPI;
use FactionsPro\FactionMain;

class FtTask extends Task {
    public $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick) {
        $p = $this->plugin;
        foreach($this->plugin->ftEntities as $ft) {
            if($ft && $ft["player"]->isOnline()) {
                $particle = $ft["particle"];
                $player = $ft["player"];
                $text = $p->getRegisteredFt($ft["creationId"])["text"];
                $text = str_replace(
                    [
                        "{player.name}",
                        "{player.display_name}",
                        "{server.online}",
                        "{server.max_online}",
                        "{player.item.name}",
                        "{player.item.id}",
                        "{player.item.meta}",
                        "{player.item.count}",
                        "{player.x}",
                        "{player.y}",
                        "{player.z}",
                        "{player.level.name}",
                        "{player.world.name}",
                        "{player.level.folder_name}",
                        "{player.world.folder_name}",
                        "{player.level.player_count}",
                        "{player.world.player_count}",
                        "{player.ip}",
                        "{player.ping}",
                        "{time}",
                        "{date}",
                        "{line}"
                    ],
                    [
                        $player->getName(),
                        $player->getDisplayName(),
                        count($p->getServer()->getOnlinePlayers()),
                        $p->getServer()->getMaxPlayers(),
                        $player->getInventory()->getItemInHand() ? $player->getInventory()->getItemInHand()->getName() : "",
                        $player->getInventory()->getItemInHand() ? $player->getInventory()->getItemInHand()->getId() : 0,
                        $player->getInventory()->getItemInHand() ? $player->getInventory()->getItemInHand()->getDamage() : 0,
                        $player->getInventory()->getItemInHand() ? $player->getInventory()->getItemInHand()->getCount() : 0,
                        $player->getFloorX(),
                        $player->getFloorY(),
                        $player->getFloorZ(),
                        $player->getLevel()->getName(),
                        $player->getLevel()->getName(),
                        $player->getLevel()->getFolderName(),
                        $player->getLevel()->getFolderName(),
                        count($player->getLevel()->getPlayers()),
                        count($player->getLevel()->getPlayers()),
                        $player->getAddress(),
                        $player->getPing(),
                        date("h:i:s A"),
                        date("j/n/Y"),
                        "\n"
                    ],
                    $text
                );
                foreach($p->getCustomTags() as $tag) {
                    $text = str_replace($tag["tag"], $tag["function"]($player), $text);
                }
                if($p->config->getNested("modules.EconomyAPI") && class_exists(EconomyAPI::class)) {
                    $text = str_replace("{player.money}", EconomyAPI::getInstance()->myMoney($player), $text);
                }
                if($p->config->getNested("modules.FactionsPro") && class_exists(FactionMain::class) && $p->getServer()->getPluginManager()->getPlugin("FactionsPro")) {
                    $text = str_replace(["{player.faction.name}", "{player.faction.power}"], [
                        $p->getServer()->getPluginManager()->getPlugin("FactionsPro")->getPlayerFaction($player),
                        $p->getServer()->getPluginManager()->getPlugin("FactionsPro")->getFactionPower($player)
                    ], $text);
                }
                if($particle->getTitle() != $text && $p->getRegisteredFt($ft["creationId"])) {
                    $particle->setTitle($text);
                    $ftt = $p->getRegisteredFt($ft["creationId"]);
                    $pos = new Position($ftt["x"], $ftt["y"], $ftt["z"], $p->getServer()->getLevelByName($ftt["level"]));
                    if($p->getServer()->isLevelGenerated($ftt["level"])) {
                        if(!$p->getServer()->isLevelLoaded($ftt["level"])) {
                            $p->getServer()->loadLevel($ftt["level"]);
                        }
                        if(!$pos->getLevel()->isChunkLoaded($pos->getX() >> 4, $pos->getZ() >> 4)) {
                            $pos->getLevel()->loadChunk($pos->getX() >> 4, $pos->getZ() >> 4);
                        }
                        $p->updateFt($ft["id"], "particle", $particle);
                    }
                } else if(!$p->getRegisteredFt($ft["creationId"])) {
                    $p->removeFt($ft["id"]);
                }
            }
        }
    }
}
