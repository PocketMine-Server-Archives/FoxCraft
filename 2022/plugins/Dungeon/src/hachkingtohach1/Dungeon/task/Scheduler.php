<?php

namespace hachkingtohach1\Dungeon\task;

use pocketmine\scheduler\Task;
use pocketmine\plugin\Plugin;
use pocketmine\player\Player;
use pocketmine\player\GameMode;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\math\Vector3;
use hachkingtohach1\Dungeon\Dungeon;
use leinne\pureentities\entity\dungeons\BossFloorOne;
use leinne\pureentities\entity\dungeons\DungeonEnderman;
use leinne\pureentities\entity\dungeons\DungeonSkeleton;
use leinne\pureentities\entity\dungeons\DungeonZombie;
use leinne\pureentities\entity\dungeons\BossFloorTwo;
use leinne\pureentities\entity\dungeons\DungeonPig;
use leinne\pureentities\entity\dungeons\DungeonIronZombie;
use leinne\pureentities\entity\dungeons\DungeonDrowned;
use hachkingtohach1\MyItem\entity\Bonemerang;

class Scheduler extends Task {
	
	private bool $loadMap = false;
	private array $timeSendMusic = [];
	private $plugin;
	
    public function __construct(Dungeon $plugin){
		$this->plugin = $plugin;
    }
	
    public function onRun() :void{
        if($this->loadMap == false){						
			foreach($this->plugin->dungeons as $case => $data){
				if(!$this->plugin->getServer()->getWorldManager()->isWorldLoaded($this->plugin->dungeons[$case]["world"])){
            		$this->plugin->getServer()->getWorldManager()->loadWorld($this->plugin->dungeons[$case]["world"]);
				} 
				foreach($this->plugin->getServer()->getWorldManager()->getWorlds() as $world){
			        if($world->getFolderName() == $this->plugin->dungeons[$case]["world"]){
					    foreach($world->getEntities() as $entity){
					        $entity->close();
						}
					}
				}
			    $this->plugin->resetMapDungeon($case);				
			}
			$this->loadMap = true;
		}			
        $this->plugin->checkEnd();
        foreach($this->plugin->dungeons as $case => $data){
			if($data["status"] == Dungeon::WAITING){
				$spawnBoss = false;
				if(count($this->plugin->dungeons[$case]["players"]) >= Dungeon::MIN_SLOTS){					
					foreach($data["players"] as $player){
						if($player->isOnline() and !$this->plugin->isSpectator($player)){
					        $timeDiff = microtime(true) - $this->plugin->dungeons[$case]["timeStart"];				
							if($timeDiff >= 30){
								$this->plugin->noCheckTp[$player->getName()] = $player;
								$this->plugin->loadLevel($data["world"]);
								$world = $this->plugin->getServer()->getWorldManager()->getWorldByName($data["world"]);				
	                    		$spawn = explode(",", $data["spawn"]);
		                		$pos = new Vector3($spawn[0], $spawn[1], $spawn[2]);
		                		$player->teleport(Position::fromObject($pos, $world));
                                $spawnBoss = true;								
							}
							$caculateTime = 30 - $timeDiff;
							$player->sendPopup(str_replace("%time", (int)$caculateTime, Dungeon::WAITING_STARTING));
						}
					}
                    if($spawnBoss == true){
						$this->plugin->spawnMobs($case);
			            $this->plugin->spawnBoss($case);
                        $this->plugin->dungeons[$case]["status"] = Dungeon::RUNNING;
			            $this->plugin->dungeons[$case]["time"] = microtime(true);						
					}						
				}else{
					if(!empty($data["players"])){
						foreach($data["players"] as $player){
							if($player->isOnline() and !$this->plugin->isSpectator($player)){
						   		$arrayA = ["%count", "%max"];
						    	$arrayB = [count($data["players"]), Dungeon::MAX_SLOTS];
						    	$player->sendPopup(str_replace($arrayA, $arrayB, Dungeon::WAITING_MORE_TIP));
							}
						}
					}
				}
			}
			if($data["status"] == Dungeon::RUNNING){
				if(!empty($data["players"])){
					foreach($data["players"] as $player){
						if($player->isOnline() and !$this->plugin->isSpectator($player)){
						    $timeDiff = microtime(true) - $data["time"];
						    $arrayA = ["%time", "%player", "%ip"];
						    $arrayB = [gmdate("H:i:s", $timeDiff), $player->getName(), Dungeon::SERVER_IP];
						    $player->sendPopup(str_replace($arrayA, $arrayB, Dungeon::IN_GAME_TIP));
						}
						if($this->plugin->isSpectator($player) and $player->isOnline()){
							if(count($data["players"]) <= 1){
								$this->plugin->dungeons[$case]["status"] = Dungeon::RESTARTING;
							}else{
								$timeDiff = microtime(true) - $data["time"];
								$timeSpec = microtime(true) - $this->plugin->spectators[$player->getName()]["time"];
						    	$arrayA = ["%time", "%player", "%ip", "%timespec"];
						    	$arrayB = [(int)(30 - $timeSpec), $player->getName(), Dungeon::SERVER_IP, $timeSpec];
						    	$player->sendPopup(str_replace($arrayA, $arrayB, Dungeon::IN_GAME_SPECTATOR_TIP));
								if($timeSpec >= 30){
									$this->plugin->noCheckTp[$player->getName()] = $player;
									$this->plugin->loadLevel($this->plugin->dungeons[$case]["world"]);
									$world = $this->plugin->getServer()->getWorldManager()->getWorldByName($this->plugin->dungeons[$case]["world"]);				
	                            	$spawn = explode(",", $this->plugin->spectators[$player->getName()]["position"]);
		                        	$pos = new Vector3($spawn[0], $spawn[1], $spawn[2]);
		                        	$player->teleport(Position::fromObject($pos, $world));
                                	unset($this->plugin->spectators[$player->getName()]);	
                                    $player->setGamemode(GameMode::SURVIVAL());									
								}
							}
						}
						if($player->isOnline()){
							if(!isset($this->timeSendMusic[$case])){
								$this->plugin->sendMusic($player);
								$this->timeSendMusic[$case] = microtime(true);
							}
							$timeDiff = microtime(true) - $this->timeSendMusic[$case];
							if($timeDiff >= 50){
								$this->plugin->sendMusic($player);
								$this->timeSendMusic[$case] = microtime(true);
							}
						}
					}
				}
			}
			if($data["status"] == Dungeon::RESTARTING){
				if(!empty($data["players"])){
					foreach($data["players"] as $player){
						if($player->isOnline()){
							if(isset($this->plugin->spectators[$player->getName()])){
								$player->setGamemode(GameMode::SURVIVAL());
								unset($this->plugin->spectators[$player->getName()]);
							}
							if($this->plugin->restarting[$case]){
								foreach($this->plugin->getServer()->getWorldManager()->getWorlds() as $world){
			                        if($world->getFolderName() == $this->plugin->dungeons[$case]["world"]){
					                    foreach($world->getEntities() as $entity){
					                        if(!$entity instanceof Player){
											    if(
													$entity instanceof BossFloorOne
													or $entity instanceof DungeonEnderman
													or $entity instanceof DungeonSkeleton
													or $entity instanceof DungeonZombie
													or $entity instanceof BossFloorTwo
													or $entity instanceof DungeonPig
													or $entity instanceof DungeonIronZombie
													or $entity instanceof DungeonDrowned
												){
											    	$entity->close();
												}
											}
										}
									}
								}
						    	$timeDiff = microtime(true) - $this->plugin->restarting[$case];
								$timeCaculate = 60 - $timeDiff;
						    	if($timeDiff >= 57){
									if(isset($this->timeSendMusic[$case])){
										unset($this->timeSendMusic[$case]);
									}
									foreach($this->plugin->getServer()->getWorldManager()->getWorlds() as $world){
			                        	if($world->getFolderName() == $this->plugin->dungeons[$case]["world"]){
					                    	foreach($world->getEntities() as $entity){
					                        	if(!$entity instanceof Player){
													if(
													    $entity instanceof BossFloorOne
													    or $entity instanceof DungeonEnderman
													    or $entity instanceof DungeonSkeleton
													    or $entity instanceof DungeonZombie
													    or $entity instanceof BossFloorTwo
													    or $entity instanceof DungeonPig
													    or $entity instanceof DungeonIronZombie
													    or $entity instanceof DungeonDrowned
													){
											    	    $entity->close();
													}
												}
											}
										}
									}					
									$player->teleport($this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
							    	if(isset($this->plugin->interactedChest[$player->getName()])){
			                            unset($this->plugin->interactedChest[$player->getName()]);
									}
									unset($this->plugin->dungeons[$case]["players"][$player->getName()]);
								}
								$player->sendPopup(str_replace("%time", (int)$timeCaculate, Dungeon::RESTARTING_TIP));
							}
						}
					}
				}
			}
		}			
    }
}