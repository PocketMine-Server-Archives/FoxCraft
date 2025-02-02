<?php

/**
 * Copyright 2020-2021 DragoVN
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace hachkingtohach1\MinePos;

use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\block\{BlockFactory, Block, BlockIds};
use hachkingtohach1\MinePos\task\Bedrock;
use hachkingtohach1\MinePos\event\EventListener;
use hachkingtohach1\MinePos\commands\MinePos as CMD;

class MinePos extends PluginBase implements Listener{
	
	public $data1 = [];
	public $data2 = [];
	public $data3 = [];
	public $data4 = [];
	public $data5 = [];
	public $data6 = [];
	public $data7 = [];
	public $setup = [];
	public $datasetup = [];
	public $events = null;
	
	public function onEnable() :void{
		$this->saveDefaultConfig();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getScheduler()->scheduleRepeatingTask(new Bedrock($this), 200);
		//$this->getServer()->getCommandMap()->register("minepos", new CMD($this));
		$this->events = new EventListener($this);
		$this->minepos = $this->newConfig($this->getDataFolder()."minepos.yml");
		$this->blockneedreplace = $this->newConfig($this->getDataFolder()."blockneedreplace.yml");				
	}

    public function onDisable() :void{
		$this->minepos->save();
		$this->blockneedreplace->save();
	}	
	
	public function newConfig($name){
		$new = new Config($name, Config::YAML);
		return $new;
	}
	
	public function saveMinePos($data1, $data2){
		$this->minepos->set($data1, $data2);
	    $this->minepos->save();		
	}
	
	public function setBlockNeed($world, $x, $y, $z, $block){
		$world->setblockAt((int)$x, (int)$y, (int)$z, BlockFactory::getInstance()->get($block, 0));
	}
}
