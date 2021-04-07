# DynamicFT
[![](https://poggit.pmmp.io/shield.state/DynamicFT)](https://poggit.pmmp.io/p/DynamicFT)
[![](https://poggit.pmmp.io/shield.api/DynamicFT)](https://poggit.pmmp.io/p/DynamicFT)
[![](https://poggit.pmmp.io/shield.dl.total/DynamicFT)](https://poggit.pmmp.io/p/DynamicFT)
[![](https://poggit.pmmp.io/shield.dl/DynamicFT)](https://poggit.pmmp.io/p/DynamicFT)

A plugin for PocketMine-MP.

Customizable floating text plugin for PocketMine-MP!

Easy and useful!

# Commands
Main command: /dynamicft

Aliases: dft

Sub commands:

create - Creates floating text

edit - Lets you edit selected floating text

remove - Removes selected floating text

listids - Shows all floating texts in a list

# API

Using plugin:

`use OguzhanUmutlu\DynamicFT\Main as DynamicFT;`

Current functions and usage:

`$dynamicft = DynamicFT::getInstance();`

`$idOfCreatedFt = $dynamicft->registerFt("This is a text!", new \pocketmine\level\Position(10, 50, 20, $dynamicft->getServer()->getLevelByName("levelName"))); // result: 0`

`$dynamicft->updateRegisteredFt($idOfCreatedFt, "text", "Now text updated!");`

`$dynamicft->getRegisteredFt($idOfCreatedFt); // result: ["text" => "Now text updated!", "x" => 10, "y" => 50, "z" => 20, "level" => "levelName", "id" => 0]`

`$dynamicft->getRegisteredFtIndex($idOfCreatedFt); // result: 0`

`$dynamicft->getAllRegisteredFts() // result: [["text" => "Now text updated!", "x" => 10, "y" => 50, "z" => 20, "level" => "levelName", "id" => 0]]`

`$idOfSpawnedFt = $dynamicft->spawnFt($idOfCreatedFt, $dynamicft->getServer()->getPlayer("aPlayerName")); // if you updated or created a registered ft it will happen automatically`

`$spawnedFt = $dynamicft->getSpawnedFt($idOfSpawnedFt); // result: ["player" => \pocketmine\Player instance, \pocketmine\level\particle\FloatingTextParticle instance, "id" => $idOfSpawnedFt, "creationId" => $idOfCreatedFt]`

`$indexOfSpawnedFt = $dynamicft->getSpawnedFtIndex($idOfSpawnedFt); // result: 0`

// if they want to update particle's text(title) use update registered ft function because every second(change in config) it edits text(title)

`$dynamicft->updateFt($idOfSpawnedFt, "player", $dynamicft->getServer()->getPlayer("anotherPlayer"));`

// they can edit text with this function:

`$newParticle = $spawnedFt["particle"];
$newParticle->setText("Hi! Im the text under the title!");`

`$dynamicft->updateFt($idOfSpawnedFt, "particle", $newParticle);`

`$dynamicft->removeFt($idOfSpawnedFt); // removes floating text for player of id`

`$dynamicft->unregisterFt($idOfCreatedFt);`

# TODO

- nothing
- plz report bugz if they find

# Reporting bugs

Enter this web site: https://github.com/OguzhanUmutlu/DynamicFT/issues

# Changelog

v1.0.0 - Added main things.
