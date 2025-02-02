<?php

namespace CLADevs\VanillaX\entities\object;

use CLADevs\VanillaX\configuration\features\ItemFeature;
use CLADevs\VanillaX\world\gamerule\GameRule;
use CLADevs\VanillaX\world\gamerule\GameRuleManager;
use CLADevs\VanillaX\session\Session;
use CLADevs\VanillaX\utils\instances\InteractButtonResult;
use CLADevs\VanillaX\utils\item\InteractButtonItemTrait;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\player\Player;
use pocketmine\Server;

class ArmorStandEntity extends Living implements InteractButtonItemTrait{

    const BUTTON_EQUIP = "Equip";
    const BUTTON_POSE = "Pose";

    const NETWORK_ID = EntityIds::ARMOR_STAND;

    const EQUIPMENT_MAINHAND = 0;
    const EQUIPMENT_HEAD = 1;
    const EQUIPMENT_CHEST = 2;
    const EQUIPMENT_LEGS = 3;
    const EQUIPMENT_FEETS = 4;

    const POSE_DEFAULT = 0;
    const POSE_NO = 1;
    const POSE_SOLEMN = 2;
    const POSE_ATHENA = 3;
    const POSE_BRANDISH = 4;
    const POSE_HONOR = 5;
    const POSE_ENTERTAIN = 6;
    const POSE_SALUTE = 7;
    const POSE_HERO = 8;
    const POSE_RIPOSTE = 9;
    const POSE_ZOMBIE = 10;
    const POSE_CANCAN_A = 11;
    const POSE_CANCAN_B = 12;

    const TAG_ARMOR = "ArmorItem";
    const TAG_MAINHAND = "MainHandItem";
    const TAG_OFFHAND = "OffHandItem";

    public float $width = 0.5;
    public float $height = 1.975;

    /** @var float */
    protected $gravity = 0.5;

    private Item $mainHand;

    private float $lastPunchTime = 0.0;

    protected function initEntity(CompoundTag $nbt): void{
        parent::initEntity($nbt);
        /** Main Hand */
        if(($tag = $nbt->getTag(self::TAG_MAINHAND)) !== null && $tag instanceof CompoundTag){
            $this->setMainHand(Item::nbtDeserialize($tag));
        }else{
            $this->mainHand = ItemFactory::air();
        }

        /** Armor */
        if(($tag = $nbt->getTag(self::TAG_ARMOR)) !== null){
            /** @var CompoundTag $item */
            foreach($tag as $item){
                $this->armorInventory->setItem($item->getByte("Slot"), Item::nbtDeserialize($item));
            }
        }
    }
    
    public function getName(): string{
        return "ArmorStand";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return new EntitySizeInfo($this->height, $this->width);
    }

    public static function getNetworkTypeId(): string{
        return self::NETWORK_ID;
    }
    
    public function saveNBT(): CompoundTag{
        $nbt = parent::saveNBT();
        /** Armor */
        $inventoryTag = new ListTag([], NBT::TAG_Compound);
        $nbt->setTag(self::TAG_ARMOR, $inventoryTag);
        for($slot = 0; $slot < 4; ++$slot){
            $item = $this->armorInventory->getItem($slot);
            if(!$item->isNull()){
                $inventoryTag->push($item->nbtSerialize($slot));
            }
        }

        /** Main Hand */
        $nbt->setTag(self::TAG_MAINHAND, $this->mainHand->nbtSerialize());
        return $nbt;
    }

    protected function sendSpawnPacket(Player $player): void{
        parent::sendSpawnPacket($player);
        $player->getNetworkSession()->sendDataPacket($this->getMainHandPacket());
    }

    public function attack(EntityDamageEvent $source): void{
        if($source instanceof EntityDamageByEntityEvent && !$this->isFlaggedForDespawn()){
            $damager = $source->getDamager();

            if($damager instanceof Player){
                if($damager->isCreative()){
                    $this->flagForDespawn();
                }else{
                    $newHealth = $this->getHealth() - $source->getFinalDamage();

                    if($newHealth < 1){
                        $this->killArmorStand();
                    }else{
                        $time = time() - $this->lastPunchTime;

                        if($time >= 0.5 && $time < 1){
                            Session::playSound($damager, "mob.armor_stand.break");
                            $this->killArmorStand();
                        }else{
                            Session::playSound($damager, "mob.armor_stand.hit");
                            $this->setHealth($newHealth);
                            $this->lastPunchTime = time();
                        }
                    }
                }
            }
        }
    }

    public function killArmorStand(): void{
        if(!$this->isFlaggedForDespawn()){
            $this->flagForDespawn();

            if(GameRuleManager::getInstance()->getValue(GameRule::DO_TILE_DROPS, $this->getWorld())){
                $items = array_merge([ItemFactory::getInstance()->get(ItemIds::ARMOR_STAND)], $this->getArmorInventory()->getContents());
                if(!$this->mainHand->isNull()){
                    $items[] = $this->mainHand;
                }
                foreach($items as $item){
                    $this->getWorld()->dropItem($this->getPosition()->add(0.5, 0.5, 0.5), $item);
                }
            }
        }
    }

    public function onMouseHover(Player $player): void{
        $player->getNetworkProperties()->setString(EntityMetadataProperties::INTERACTIVE_TAG, $player->isSneaking() ? self::BUTTON_POSE : self::BUTTON_EQUIP);
    }

    public function onButtonPressed(InteractButtonResult $result): void{
        if(!$this->getArmorInventory() instanceof ArmorInventory){
            return;
        }
        
    }

    public function handleMainHandItem(Player $player, Item $old, Item $new): void{
        if($new->getId() === ItemIds::ARMOR_STAND){
            return;
        }
        if(!$old->isNull()){
            $player->getInventory()->setItemInHand($old);
        }else{
            $player->getInventory()->setItemInHand(ItemFactory::air());
        }
        $this->setMainHand($new);
        Session::playSound($player, "mob.armor_stand.place");
    }

    public function handleArmorItem(Player $player, Item $old, Item $new, int $slot): void{
        if(!$old->isNull()){
            $player->getInventory()->setItemInHand($old);
        }else{
            $player->getInventory()->setItemInHand(ItemFactory::air());
        }
        $this->getArmorInventory()->setItem($slot, $new);
        Session::playSound($player, "mob.armor_stand.place");
    }

    public function getPose(): int{
        return $this->getNetworkProperties()->getAll[EntityMetadataProperties::ARMOR_STAND_POSE_INDEX] ?? self::POSE_DEFAULT;
    }

    public function setPose(int $pose, bool $strict = true): void{
        if($strict && ($pose > self::POSE_CANCAN_B || $pose < self::POSE_DEFAULT)){
            $pose = self::POSE_DEFAULT;
        }
        $this->getNetworkProperties()->setInt(EntityMetadataProperties::ARMOR_STAND_POSE_INDEX, $pose);
    }

    public function getMainHand(): Item{
        return $this->mainHand;
    }

    public function setMainHand(Item $mainHand): void{
        $this->mainHand = $mainHand;
        Server::getInstance()->broadcastPackets($this->getViewers(), [$this->getMainHandPacket()]);
    }

    public function getMainHandPacket(Item $item = null): MobEquipmentPacket{
        if($item === null){
            $item = $this->mainHand;
        }
        $pk = new MobEquipmentPacket();
        $pk->actorRuntimeId = $this->id;
        $pk->item = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($item));
        $pk->inventorySlot = 0;
        $pk->hotbarSlot = $pk->inventorySlot;
        return $pk;
    }

    public function getClickedPosSlot(float $i): int{
        if($i >= 1.6 && !$this->armorInventory->getHelmet()->isNull()){
            return self::EQUIPMENT_HEAD;
        }elseif(($i >= 0.4 && $i < 1.2) && !$this->armorInventory->getChestplate()->isNull()){
            return self::EQUIPMENT_CHEST;
        }elseif(($i >= 0.9 && $i < 1.6) && !$this->armorInventory->getLeggings()->isNull()){
            return self::EQUIPMENT_LEGS;
        }elseif(($i >= 0.1 && $i < 0.55) && !$this->armorInventory->getBoots()->isNull()){
            return self::EQUIPMENT_FEETS;
        }
        return self::EQUIPMENT_MAINHAND;
    }

    public static function canRegister(): bool{
        return ItemFeature::getInstance()->isItemEnabled(ItemIds::ARMOR_STAND);
    }
}