<?php

declare(strict_types=1);

namespace leinne\pureentities\task;

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
use leinne\pureentities\entity\vehicle\Boat;
use leinne\pureentities\entity\other\God;
use leinne\pureentities\entity\other\SpecialEnderman;
use leinne\pureentities\entity\other\SpecialGolem;
use leinne\pureentities\entity\other\SpecialZombie;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\block\Water;
use pocketmine\entity\Location;
use pocketmine\world\World;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use leinne\pureentities\PureEntities;
use hachkingtohach1\Dungeon\Dungeon;

class AutoSpawnTask extends Task{
	
	private $checked = [];
	
	public function __construct(PureEntities $plugin){
        $this->plugin = $plugin;
	}	

    public function onRun() : void{
        foreach(Server::getInstance()->getOnlinePlayers() as $k => $player){	            				           
			if(!Dungeon::getInstance()->inGame($player)){
				$time = $player->getWorld() ? $player->getWorld()->getTime() % World::TIME_FULL : World::TIME_NIGHT;		    						
				if($time >= World::TIME_NIGHT and $time <= World::TIME_MIDNIGHT){
					if(!isset($this->checked[$player->getName()])){
					    $player->sendMessage("§l§c[CẢNH BÁO]§r§c Các động vật hiện đã bị biến mất thay vào đó cho những quái thú đáng sợ và những phương tiện như thuyền sẽ bị vỡ hãy cẩn thận!");
					    $this->checked[$player->getName()] = $player;
					}
				}
				if($player->isSurvival() and $time >= World::TIME_NIGHT){					
					if(rand(1, 1500) <= 500){           
                    	$radX = mt_rand(3, 50);
                    	$radZ = mt_rand(3, 50);
                    	$pos = $player->getLocation()->floor();
                    	$pos->y = $player->getWorld()->getHighestBlockAt($pos->x += mt_rand(0, 1) ? $radX : -$radX, $pos->z += mt_rand(0, 1) ? $radZ : -$radZ) + 1;

                    	$entityClasses = [
                        	[Zombie::class, Creeper::class, IronGolem::class],//, "Slime", "Wolf", "Ocelot", "Mooshroom", "Rabbit", "IronGolem", "SnowGolem"],
                        	[Skeleton::class, Spider::class, Zombie::class] //ZombifiedPiglin::class]//, "Enderman", "CaveSpider", "MagmaCube", "ZombieVillager", "Ghast", "Blaze"]
                    	];
                    	$entity = new $entityClasses[mt_rand(0, 1)][mt_rand(0, 2)](Location::fromObject($pos, $player->getWorld()));
                    	$entity->spawnToAll();
					}
					foreach($this->plugin->getServer()->getWorldManager()->getWorlds() as $world){
			            foreach($world->getEntities() as $entity){
							if(
							    $entity instanceof Cow ||
								$entity instanceof Pig ||
								$entity instanceof Sheep ||
								$entity instanceof Chicken ||
								$entity instanceof Mooshroom ||
								$entity instanceof Boat 
							){
								$entity->close();
							}
						}
					}
				}
				if($time < World::TIME_NIGHT || $time > World::TIME_SUNRISE){
					foreach($this->plugin->getServer()->getWorldManager()->getWorlds() as $world){
			            foreach($world->getEntities() as $entity){
							if(
							    $entity instanceof Zombie ||
								$entity instanceof Creeper ||
								$entity instanceof IronGolem ||
								$entity instanceof Skeleton ||
								$entity instanceof Spider ||
								$entity instanceof God ||
								$entity instanceof SpecialEnderman ||
								$entity instanceof SpecialZombie ||
								$entity instanceof SpecialGolem
							){
								$entity->close();
							}
						}
					}
					if(isset($this->checked[$player->getName()])){
					    unset($this->checked[$player->getName()]);
					}
				}
				if($player->isSurvival() and rand(1, 1500) <= 500){           
                	$radX = mt_rand(3, 50);
                	$radZ = mt_rand(3, 50);
                	$pos = $player->getLocation()->floor();
                	$pos->y = $player->getWorld()->getHighestBlockAt($pos->x += mt_rand(0, 1) ? $radX : -$radX, $pos->z += mt_rand(0, 1) ? $radZ : -$radZ) + 1;

                	$entityClasses = [
                    	[Cow::class, Pig::class, Sheep::class],//, "Slime", "Wolf", "Ocelot", "Mooshroom", "Rabbit", "IronGolem", "SnowGolem"],
                    	[Chicken::class, Sheep::class, Cow::class] //ZombifiedPiglin::class]//, "Enderman", "CaveSpider", "MagmaCube", "ZombieVillager", "Ghast", "Blaze"]
                	];
                	$entity = new $entityClasses[mt_rand(0, 1)][mt_rand(0, 2)](Location::fromObject($pos, $player->getWorld()));
                	$entity->spawnToAll();
				}
			}
        }
    }

}