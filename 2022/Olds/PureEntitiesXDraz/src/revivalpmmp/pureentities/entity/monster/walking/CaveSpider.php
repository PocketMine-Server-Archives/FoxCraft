<?php

/**
 * PureEntitiesX: Mob AI Plugin for PMMP
 * Copyright (C)  2018 RevivalPMMP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace revivalpmmp\pureentities\entity\monster\walking;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use revivalpmmp\pureentities\utils\MobDamageCalculator;

class CaveSpider extends WalkingMonster{
	const NETWORK_ID = Data::NETWORK_IDS["cave_spider"];

	public function initEntity() : void{
		parent::initEntity();
		$this->speed = 1.3;
		$this->setDamage([0, 2, 3, 3]);
	}

	public function getName() : string{
		return "CaveSpider";
	}

	/**
	 * Attack the player
	 *
	 * @param Entity $player
	 */
	public function attackEntity(Entity $player){
		if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.32){
			$this->attackDelay = 0;
			$ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK,
				MobDamageCalculator::calculateFinalDamage($player, $this->getDamage()));
			$player->attack($ev);
			$poisonInstance = new EffectInstance(Effect::getEffect(Effect::POISON));
			// TODO: Change Effect details to work with difficulty settings.
			$poisonInstance->setAmplifier(1);
			Effect::getEffect(Effect::POISON)->applyEffect($player, $poisonInstance);
			$this->checkTamedMobsAttack($player);
		}
	}

	public function getDrops() : array{
		return [];
	}

	public function updateXpDropAmount() : void{

	}


}
