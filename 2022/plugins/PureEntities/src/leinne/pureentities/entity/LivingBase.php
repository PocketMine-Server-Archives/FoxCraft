<?php

declare(strict_types=1);

namespace leinne\pureentities\entity;

use leinne\pureentities\entity\ai\navigator\EntityNavigator;
use pocketmine\utils\TextFormat;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\player\Player;
use pocketmine\world\World;

abstract class LivingBase extends Living{

    protected $stepHeight = 0.6;

    protected $jumpVelocity = 0.52;

    private float $speed = 1.0;
	
	private int $addHealth = 0;

    protected int $interactDelay = 0;

    protected bool $breakDoor = false;

    protected bool $fixedTarget = false;

    protected ?EntityNavigator $navigator = null;

    public abstract function getDefaultNavigator() : EntityNavigator;

    public function getNavigator() : EntityNavigator{
        return $this->navigator ?? $this->navigator = $this->getDefaultNavigator();
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setMaxHealth($health = $nbt->getInt("MaxHealth", $this->getDefaultMaxHealth()) + $this->addHealth);
        if($nbt->getTag("HealF") instanceof FloatTag){
            $health = (float) ($nbt->getFloat("HealF") + $this->addHealth);
        }elseif($nbt->getTag("Health") instanceof FloatTag or $nbt->getTag("Health") instanceof IntTag){
            $healthTag = $nbt->getTag("Health");
            $health = (float) ($healthTag->getValue() + $this->addHealth);
        }

        $this->setHealth($health);
        $this->setImmobile();
    }

    /** 상호작용을 위한 최소 거리 */
    public function getInteractDistance() : float{
        return 0.6;
    }

    /** 상호작용이 가능한 거리인지 체크 */
    public function canInteractTarget() : bool{
        $target = $this->getTargetEntity();
        if($target === null){
            return false;
        }

        $width = $this->getInteractDistance() + ($this->size->getWidth() + $target->size->getWidth()) / 2;
        return abs($this->location->x - $target->location->x) <= $width
            && abs($this->location->z - $target->location->z) <= $width
            && abs($this->location->y - $target->location->y) <= min(1, $this->size->getEyeHeight());
    }

    public function canInteractWithTarget(Entity $target, float $distanceSquare) : bool{
        return $this->fixedTarget || ($target instanceof Living && $distanceSquare <= 10000);
    }

    /** 타겟과의 상호작용 */
    public function interactTarget() : bool{
        ++$this->interactDelay;
        if(!$this->canInteractTarget()){
            return false;
        }
        return true;
    }

    public function interact(Player $player, Item $item) : bool{
        return false;
    }

    public function getDefaultMaxHealth() : int{
        return 20;
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setInt("MaxHealth" , $this->getMaxHealth());
        return $nbt;
    }

    public function onUpdate(int $currentTick) : bool{
		$this->setNametag($this->getName()." ".TextFormat::RESET.TextFormat::RED.number_format((int)$this->getHealth()));
        if(!$this->canSpawnPeaceful() && $this->getWorld()->getDifficulty() === World::DIFFICULTY_PEACEFUL){
            $this->flagForDespawn();
            return false;
        }
		$block = $this->getWorld()->getBlockAt((int)$this->getLocation()->x, (int)$this->getLocation()->y + 1, (int)$this->getLocation()->z);
		if($block->getId() == 8 || $block->getId() == 9){
			$this->motion->y += 0.2;
		    $this->move($this->motion->x, $this->motion->y, $this->motion->z);
			$this->updateMovement();
		}
        return parent::onUpdate($currentTick);
    }

    public function isMovable() : bool{
        return true;
    }

    public function canBreakDoors() : bool{
        return $this->breakDoor;
    }

    public function canSpawnPeaceful() : bool{
        return true;
    }

    public function updateMovement(bool $teleport = false) : void{
        $send = false;
        $pos = $this->getLocation();
        $last = $this->lastLocation;
        if(
            $last->x !== $pos->x
            || $last->y !== $pos->y
            || $last->z !== $pos->z
            || $last->yaw !== $pos->yaw
            || $last->pitch !== $pos->pitch
        ){
            $send = true;
            $this->lastLocation = $this->getLocation();
        }

        if(
            $this->lastMotion->x !== $this->motion->x
            || $this->lastMotion->y !== $this->motion->y
            || $this->lastMotion->z !== $this->motion->z
        ){
            $this->lastMotion = clone $this->motion;
        }

        if($send){
            $this->broadcastMovement($teleport);
        }
    }

    public function getSpeed() : float{
        return $this->speed;
    }

    public function setSpeed(float $speed) : void{
        $this->speed = $speed;
    }
	
	public function setAddHealth(int $health) : void{
        $this->addHealth = $health;
    }

    public function setTargetEntity(?Entity $target, bool $fixed = false) : void{
        parent::setTargetEntity($target);
        if($target !== null){
            $this->getNavigator()->setGoal($target->getPosition());
        }
        $this->fixedTarget = $fixed;
    }

    public function setMotion(Vector3 $motion) : bool{
        $return = parent::setMotion($motion);
        $this->getNavigator()->updateGoal();
        return $return;
    }

}
