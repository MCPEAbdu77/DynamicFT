
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

#### Floating text properties
| Property name | Data type |
|--|--|
| `text` | string |
| `x` | float |
| `y` | float |
| `z` | float |
| `level` | string *(Level folder name)* |

### Getting floating text properties

```php
$dynamicft->fts[$id];
```
This function returns all the properties of the floating text in array

```php
$dynamicft->fts;
```
This function returns all floating texts

### Spawn floating texts to client (player)

```php
$idOfSpawnedFt = $dynamicft->spawnFt($id, $dynamicft->getServer()->getPlayer("aPlayerName"));
```
This process will be run automatically when a player joins, when a floating text gets registered or gets updated

```php
$dynamicft->ftEntities[idOfSpawnedFt];
```
This returns the all the floating text particle instances detail of a floating text in array

#### Result
```php
/*
[
	"player" => \pocketmine\Player instance,
	"particle" => \pocketmine\level\particle\FloatingTextParticle instance,
	"id" => int
]
*/
$idOfSpawnedFt = $spawnedFt["id"];
```

**If you want to update the text of a floating text, update with the `$dynamicft->ftConfig->getAll()` and `$dynamicft->ftConfig->setAll()` functions instead of changing the text of the floating text particle instance as it will be overwritten every second**

# Reporting bugs
**You may open an issue on the floating text GitHub repository for report bugs**
https://github.com/OguzhanUmutlu/DynamicFT/issues
