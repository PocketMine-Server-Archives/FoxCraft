<?php

namespace Vecnavium\LaunchableTNT;

use pocketmine\plugin\PluginBase;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\block\Solid;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\item\{Item, ItemIds};
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\Listener;
use pocketmine\entity\object\FallingBlock;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\utils\Random;
use pocketmine\world\sound\IgniteSound;

class LaunchableTNT extends PluginBase implements Listener{

    public function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
	public function createBaseNBT(Vector3 $pos, ?Vector3 $motion = null, float $yaw = 0.0, float $pitch = 0.0) : CompoundTag{
		$nbt = CompoundTag::create()
	    ->setTag("Pos", new ListTag([
            new DoubleTag($pos->x),
			new DoubleTag($pos->y),
			new DoubleTag($pos->z)
		]))
		->setTag("Motion", new ListTag([
			new DoubleTag($motion !== null ? $motion->x : 0.0),
			new DoubleTag($motion !== null ? $motion->y : 0.0),
			new DoubleTag($motion !== null ? $motion->z : 0.0)
		]))
		->setTag("Rotation", new ListTag([
			new FloatTag($yaw),
			new FloatTag($pitch)
		]));
		return $nbt;
	}
	
	public function onExplosionPrime(ExplosionPrimeEvent $event): void
	{
		$event->setBlockBreaking(false);
	}

    public function onExplode(EntityExplodeEvent $event): void
    {
        foreach ($event->getBlockList() as $block) {
            if ($block instanceof Solid) {
                $nbt = Entity::createBaseNBT($block);
                $nbt->setInt("TileID", $block->getId());
                $nbt->setByte("Data", $block->getDamage());
            }
        }
    }

    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
		$block = $event->getBlock();
		$position = $block->getPosition();
        if ($player->getInventory()->getItemInHand()->getCustomName() != "Infinite TNT" and $player->getInventory()->getItemInHand()->getId() === ItemIds::TNT) {
			$mot = (new Random())->nextSignedFloat() * M_PI * 2;
		    $tnt = new PrimedTNT(Location::fromObject($position->add(0.5, 0, 0.5), $position->getWorld()));
		    $tnt->setFuse(80);
		    $tnt->setWorksUnderwater(false);
		    $tnt->setMotion(new Vector3(-sin($mot) * 0.02, 0.2, -cos($mot) * 0.02));
		    $tnt->spawnToAll();
		    $tnt->broadcastSound(new IgniteSound());
			$count = $player->getInventory()->getItemInHand()->getCount();
			$player->getInventory()->setItemInHand($player->getInventory()->getItemInHand()->setCount($count - 1));
            $event->cancel();
		}
    }


}