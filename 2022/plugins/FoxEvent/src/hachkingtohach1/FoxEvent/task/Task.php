<?php

namespace hachkingtohach1\FoxEvent\task;

use pocketmine\scheduler\Task as TaskPM;
use pocketmine\plugin\Plugin;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use hachkingtohach1\FoxEvent\FoxEvent;

class Task extends TaskPM {
    
	private $timeRestart = null;
	private $timeStart = null;
	private $plugin;
	
    public function __construct(FoxEvent $plugin){
		$this->plugin = $plugin;
    }
	
    public function onRun() :void{		
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
			if($this->timeStart == null and $this->timeRestart == null){
				$this->timeStart = microtime(true);
			}
			if($this->timeStart != null){
				$timeDiff = microtime(true) - $this->timeStart;
				if($timeDiff >= 900){
					if($this->timeRestart == null){
				    	$this->timeRestart = microtime(true);
					}
					$this->plugin->randomEvent();
					$this->timeStart = null;	
				}					
			}
			if($this->timeRestart != null){
				$timeDiff = microtime(true) - $this->timeRestart;
				if($timeDiff >= 900){					
					$this->timeRestart = null;
                    $this->plugin->resetEvent();					
				}
			}
		}			
    }
}