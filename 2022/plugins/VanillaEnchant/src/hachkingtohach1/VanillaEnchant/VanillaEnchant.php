<?php

namespace hachkingtohach1\VanillaEnchant;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\inventory\Inventory;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Location;
use pocketmine\world\Position;
use pocketmine\world\World;
use leinne\pureentities\entity\hostile\Creeper;
use leinne\pureentities\entity\hostile\Skeleton;
use leinne\pureentities\entity\hostile\Zombie;
use leinne\pureentities\entity\neutral\IronGolem;
use leinne\pureentities\entity\neutral\Spider;
use leinne\pureentities\entity\neutral\ZombifiedPiglin;
use leinne\pureentities\entity\passive\Chicken;
use leinne\pureentities\entity\passive\Cow;
use leinne\pureentities\entity\passive\Mooshroom;
use leinne\pureentities\entity\passive\Pig;
use leinne\pureentities\entity\passive\Sheep;
use leinne\pureentities\entity\other\God;
use leinne\pureentities\entity\other\SpecialEnderman;
use leinne\pureentities\entity\other\SpecialGolem;
use leinne\pureentities\entity\dungeons\BossFloorOne;
use leinne\pureentities\entity\dungeons\DungeonEnderman;
use leinne\pureentities\entity\dungeons\DungeonSkeleton;
use leinne\pureentities\entity\dungeons\DungeonZombie;
use leinne\pureentities\entity\dungeons\BossFloorTwo;
use leinne\pureentities\entity\dungeons\DungeonPig;
use leinne\pureentities\entity\dungeons\DungeonIronZombie;
use leinne\pureentities\entity\dungeons\DungeonDrowned;
use leinne\pureentities\entity\vehicle\Boat;

class VanillaEnchant extends PluginBase implements Listener {
	
	private array $useThorn = [];
	private array $deaths = [];
	private static $instance = null;
	
	public function onLoad() :void{
        self::$instance = $this;
	}
	
	public static function getInstance(): VanillaEnchant{
        return self::$instance;
    }

    public function onEnable() :void{
		$this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
	
	public function checkEnchantment(Item $item, int $enchant) :int{
		$result = 0;
		foreach($item->getEnchantments() as $enchantmentInstance){
			if(EnchantmentIdMap::getInstance()->toId($enchantmentInstance->getType()) == $enchant){
			    $result = $enchantmentInstance->getLevel();
			}
		}
		return $result;
	}
	
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event){
		$damager = $event->getDamager();
		$entity = $event->getEntity();
		if($damager instanceof Player){
			$itemInHand = $damager->getInventory()->getItemInHand();
			if($level = $this->checkEnchantment($itemInHand, 11) > 0){ //Bane of Arthropods
				$event->setBaseDamage($event->getBaseDamage() + (2.5 * $level));
			}			
		}
		if($entity instanceof Player){
			$armors = [
		        $entity->getArmorInventory()->getHelmet(),
			    $entity->getArmorInventory()->getChestplate(),
			    $entity->getArmorInventory()->getLeggings(),
			    $entity->getArmorInventory()->getBoots()
		    ];
			foreach($armors as $armor){
				if($level = $this->checkEnchantment($armor, 5) > 0){ //Thorns
				    if(!isset($this->useThorns[$entity->getName()])){
						$this->useThorns[$entity->getName()] = microtime(true);
					}
					if(rand(1, 100) <= 15 * $level){					    
						if(microtime(true) - $this->useThorns[$entity->getName()] >= 2){
						    $ev = new EntityDamageByEntityEvent($entity, $damager, EntityDamageEvent::CAUSE_CUSTOM, 2);
                            $damager->attack($ev);
							$this->useThorns[$entity->getName()] = microtime(true);
						}
					}
				}
			}
		}
	}
}