<?php

namespace hachkingtohach1\FishingRod\task;

use pocketmine\scheduler\Task;
use pocketmine\plugin\Plugin;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
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
use hachkingtohach1\FishingRod\FishingRod;
use hachkingtohach1\PlayerStats\PlayerStats;
use hachkingtohach1\FCCore\FCCore;
use Vecnavium\SkyBlocksPM\SkyBlocksPM;

class CatchTime extends Task {
	
	private array $isWater = [];
	private array $check = [];
	private array $timePlayer = [];
	private array $isFlying = [];
	private $timeClear = null;
	private $timeRestart = null;
	private $plugin;
	
    public function __construct(FishingRod $plugin){
		$this->plugin = $plugin;
    }
	
    public function onRun() :void{
		
		foreach($this->plugin->getServer()->getWorldManager()->getWorlds() as $world){    
			foreach($world->getEntities() as $entity){
			    $maxDistance = 2;			
			    foreach($entity->getWorld()->getNearbyEntities($entity->getBoundingBox()->expandedCopy($maxDistance, $maxDistance, $maxDistance), $entity) as $entities){
				    if($entities instanceof ItemEntity and $entity instanceof ItemEntity){
				        $entities->teleport(new Vector3($entity->getLocation()->x, $entity->getLocation()->y, $entity->getLocation()->z));
					}
				}
			}
		}
		
		if($this->timeRestart == null){
			$this->timeRestart = microtime(true);
		}           	
        $timeDiffR = microtime(true) - $this->timeRestart;
        $timeCaculateR = (int)(3600 - $timeDiffR);	
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
            if(in_array($timeCaculateR, [1, 2, 3, 4, 5, 30, 40, 50, 60])){				
	            $player->sendMessage("§l§c[CẢNH BÁO]§r§c Khởi động lại server trong §e".$timeCaculateR."§c giây!"); 
			}		           
		}
		if($timeCaculateR <= 1){
			foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
				$player->transfer("foxcraft.zapto.org", 19132);
			}
			$this->plugin->getServer()->shutdown();
			return;
		}
		
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
			
			$time = (int)FCCore::getInstance()->getConfig()->getNested("database.".$player->getName().".vip") - microtime(true);
            if(isset($time)){
				if($time >= 2592000 and !$this->plugin->getServer()->isOp($player->getName())){
				    $pure = $this->plugin->getServer()->getPluginManager()->getPlugin('PurePerms');
                    $userGroup = $pure->getUserDataMgr()->getGroup($player);    
                    $group = $pure->getGroup("Vip+");					
					if($userGroup == "Vip++"){
						$pure->getUserDataMgr()->setGroup($player, $group, null);
					}
				}
			}
			if(isset(FCCore::getInstance()->hasJoined[$player->getXuid()])){
				$pure = $this->plugin->getServer()->getPluginManager()->getPlugin('PurePerms');
            	$userGroup = $pure->getUserDataMgr()->getGroup($player);  
				if($userGroup == "Vip++"){
			    	$skyblock = SkyBlocksPM::getInstance()->getPlayerManager()->getPlayer($player)->getSkyblock();
                	if($skyblock != '' and SkyBlocksPM::getInstance()->getSkyBlockManager()->getSkyBlockByUuid($skyblock) != null){                    	
						$world = $this->plugin->getServer()->getWorldManager()->getWorldByName(SkyBlocksPM::getInstance()->getSkyBlockManager()->getSkyBlockByUuid($skyblock)->getWorld());	
				    	if($player->getWorld()->getDisplayName() == $world->getDisplayName()){
							if(!isset($this->isFlying[$player->getName()])){
							    if(!$player->isCreative()){
								    $player->setFlying(true);
					    	        $player->setAllowFlight(true);
								    $this->isFlying[$player->getName()] = $player;
								}
							}
						}else{
							if(isset($this->isFlying[$player->getName()])){
							    if(!$player->isCreative()){
						    	    $player->setFlying(false);
					        	    $player->setAllowFlight(false);
									unset($this->isFlying[$player->getName()]);
								}
							}
						}
					}
				}
			}
			if($this->timeClear == null){
				$this->timeClear = microtime(true);
			}	
            if($this->timeRestart == null){
				$this->timeRestart = microtime(true);
			}           	
            $timeDiff = microtime(true) - $this->timeClear;	
            $timeCaculate = (int)(200 - $timeDiff);		
            if(in_array($timeCaculate, [1, 2, 3, 4, 5, 30, 40, 50, 60])){				
				$player->sendMessage("§l§c[CẢNH BÁO]§r§c Dọn rác trong §b".$timeCaculate."§c giây!"); 
			}
            $timeDiffR = microtime(true) - $this->timeRestart;
            $timeCaculateR = (int)(3600 - $timeDiffR);	
            if(in_array($timeCaculateR, [1, 2, 3, 4, 5, 30, 40, 50, 60])){				
				$player->sendMessage("§l§c[CẢNH BÁO]§r§c Khởi động lại server trong §e".$timeCaculateR."§c giây!"); 
			}		
            if($timeCaculateR <= 1){
				foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
					$player->transfer("foxcraft.zapto.org", 19132);
				}
				$this->plugin->getServer()->shutdown();
				return;
			}				
            if($timeCaculate <= 1){
				foreach($this->plugin->getServer()->getWorldManager()->getWorlds() as $world){
			        foreach($world->getEntities() as $entity){
						if(
							$entity instanceof ItemEntity ||
							$entity instanceof ExperienceOrb ||
							$entity instanceof Cow ||
								$entity instanceof Pig ||
								$entity instanceof Sheep ||
								$entity instanceof Chicken ||
								$entity instanceof Mooshroom ||
								$entity instanceof Boat ||
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
				$this->timeClear = microtime(true);
			}
			if(isset($this->plugin->fishing[$player->getName()])){
				$entity = $this->plugin->fishing[$player->getName()][1];
				if($this->plugin->fishing[$player->getName()][0]){
                    $distance = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1, 1.1, 1.2, 1.3];
					foreach($distance as $dis){
						if(!$this->plugin->getServer()->getWorldManager()->isWorldLoaded($entity->getWorld()->getDisplayName())){
							return;
						}
						$block = $entity->getWorld()->getBlock(new Vector3($entity->getLocation()->x, $entity->getLocation()->y - $dis, $entity->getLocation()->z));
						if($block->getId() == 8 or $block->getId() == 9){
							if(!isset($this->isWater[$player->getName()])){
								$this->isWater[$player->getName()] = $player;
							}
						}
					}
					if(!isset($this->isWater[$player->getName()])){
						$entity->setNametag(TextFormat::BOLD.TextFormat::RED."Chưa chuẩn");
					    $entity->setNameTagVisible(true);
                        $entity->setNameTagAlwaysVisible(true);
					}
					if(isset($this->isWater[$player->getName()])){
						$entity = $this->plugin->fishing[$player->getName()][1];
						if(!isset($this->timePlayer[$player->getName()])){
							$this->timePlayer[$player->getName()] = microtime(true);
						}
						$timeDiff = microtime(true) - $this->timePlayer[$player->getName()];
						if($timeDiff >= 5 and $timeDiff < 8){
							$entity->attractFish();
						}
						$entity->setNametag(TextFormat::BOLD.TextFormat::RED."[!]");
						if($timeDiff >= 8 and $timeDiff <= 12){
							$entity->setNameTagVisible(true);
                            $entity->setNameTagAlwaysVisible(true);
							$entity->fishBites();
							//$player->sendTitle(TextFormat::BOLD.TextFormat::RED."[!]");
							$this->check[$player->getName()] = $timeDiff;
						}
						if($timeDiff > 12){
							$entity->setNametag("");
							$entity->setNameTagVisible(false);
                            $entity->setNameTagAlwaysVisible(false);
							$this->timePlayer[$player->getName()] = microtime(true);
							if(isset($this->check[$player->getName()])){
						    	unset($this->check[$player->getName()]);
							}
						}
					}
				}else{
					if(isset($this->isWater[$player->getName()])){
						unset($this->isWater[$player->getName()]);
					}
					if(isset($this->timePlayer[$player->getName()])){
						unset($this->timePlayer[$player->getName()]);
					}
                    if(isset($this->plugin->pullOut[$player->getName()])){
						if(isset($this->check[$player->getName()])){
						    $check = (int)$this->check[$player->getName()];
						    if($check <= 12){
							    $this->plugin->reward($player);
								PlayerStats::getInstance()->checkLevel($player, rand(1, 20));
							}
						}
						unset($this->plugin->pullOut[$player->getName()]);
					}	
                    if(isset($this->check[$player->getName()])){
						unset($this->check[$player->getName()]);
					}					
				}
			}
		}			
    }
}