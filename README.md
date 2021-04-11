
# DynamicFT
[![](https://poggit.pmmp.io/shield.state/DynamicFT)](https://poggit.pmmp.io/p/DynamicFT)
[![](https://poggit.pmmp.io/shield.api/DynamicFT)](https://poggit.pmmp.io/p/DynamicFT)
[![](https://poggit.pmmp.io/shield.dl.total/DynamicFT)](https://poggit.pmmp.io/p/DynamicFT)
[![](https://poggit.pmmp.io/shield.dl/DynamicFT)](https://poggit.pmmp.io/p/DynamicFT)

A useful PocketMine-MP plugin that allow user to create customizable dynamic floating text easily!

# Commands
Main command: `/dynamicft` (Aliases: `dft`)
Sub commands:
- `create` - Creates floating text
- `edit` - Lets you edit selected floating text
- `remove` - Removes selected floating text
- `listids` - Shows all floating texts in a list

# Tags

## General tags

- `{player.name}`
- `{player.display_name}`
- `{server.online}`
- `{server.max_online}`
- `{player.item.name}`
- `{player.item.id}`
- `{player.item.meta}`
- `{player.item.count}`
- `{player.x}`
- `{player.y}`
- `{player.z}`
- `{player.level.name}`
- `{player.world.name}`
- `{player.level.folder_name}`
- `{player.world.folder_name}`
- `{player.level.player_count}`
- `{player.world.player_count}`
- `{player.ip}`
- `{player.ping}`
- `{time}`
- `{date}`
- `{line}`

## EconomyAPI extension
- `{player.money}`

## FactionsPro extension
- `{player.faction.name}`
- `{player.faction.power}`

# API

### Getting the plugin main class

```php
use OguzhanUmutlu\DynamicFT\Main as DynamicFT;
```

```php
$dynamicft = DynamicFT::getInstance();
```

### Register floating texts / Alter floating text properties
```php
$idOfCreatedFt = $dynamicft->registerFt("This is a text!", new \pocketmine\level\Position(10, 50, 20, $dynamicft->getServer()->getLevelByName("levelName")));
```
This function returns the ID of the floating text

```php
$dynamicft->updateRegisteredFt($idOfCreatedFt, "propertyName", "Property data (mixed)");
```

#### Floating text properties
| Property name | Data type |
|--|--|
| `text` | string |
| `x` | float |
| `y` | float |
| `z` | float |
| `level` | string *(Level folder name)* |
| `id` | int |

### Getting floating text properties

```php
$dynamicft->getRegisteredFt($idOfCreatedFt);
```
This function returns all the properties of the floating text in array

```php
$dynamicft->getRegisteredFtIndex($idOfCreatedFt);
```
This function returns the floating properties index of `fts.yml`

```php
$dynamicft->getAllRegisteredFts();
```
This function returns the properties of every floating text

### Spawn floating texts to client (player)

```php
$idOfSpawnedFt = $dynamicft->spawnFt($idOfCreatedFt, $dynamicft->getServer()->getPlayer("aPlayerName"));
```
This process will be run automatically when a player joins, when a floating text gets registered or gets updated

```php
$spawnedFt = $dynamicft->getSpawnedFt($idOfSpawnedFt);
```
This functions returns the all the floating text particle instances detail of a floating text in array

#### Result
```php
/*
[
	"player" => \pocketmine\Player instance,
	"particle" => \pocketmine\level\particle\FloatingTextParticle instance,
	"id" => int,
	"creationId" => int$idOfCreatedFt
]
*/
$idOfSpawnedFt = $spawnedFt["id"];
```

**If you want to update the text of a floating text, update with the `updateRegisteredFT()` function instead of changing the text of the floating text particle instance as it will be overwritten every second**

# Reporting bugs
**You may open an issue on the floating text GitHub repository for report bugs**
https://github.com/OguzhanUmutlu/DynamicFT/issues
