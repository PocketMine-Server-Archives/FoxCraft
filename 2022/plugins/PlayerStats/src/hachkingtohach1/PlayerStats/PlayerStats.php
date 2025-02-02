<?php

declare(strict_types=1);

namespace hachkingtohach1\PlayerStats;

use pocketmine\player\Player;
use pocketmine\entity\Entity;
use pocketmine\entity\{EntityFactory, EntityDataHelper};
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use pocketmine\world\sound\PotionSplashSound;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\item\enchantment\Enchantment; 
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket; 
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\entity\effect\{Effect, EffectInstance};
use pocketmine\nbt\tag\{ByteTag, CompoundTag, DoubleTag, FloatTag, StringTag, ListTag, ShortTag, IntTag};
use hachkingtohach1\PlayerStats\provider\{DataBase, sql\SQL};
use hachkingtohach1\PlayerStats\task\Popup;
use hachkingtohach1\PlayerStats\task\Damage;
use hachkingtohach1\PlayerStats\entity\DamageIndicator;
use hachkingtohach1\CustomPet\entity\ShapePet;
use hachkingtohach1\MyItem\entity\Bubble;
use hachkingtohach1\MyItem\entity\Throww;
use hachkingtohach1\NPC\entity\NPCEntity;

class PlayerStats extends PluginBase implements Listener {
	
	/* id => [id, meta] */
	public $blocks = [];
	public $intelligence =[];
	public $addIntelligence =[];
	public $critChance =[];
	public $speed = [];
	public $pets = [];
	public $updateStats = [];
	public $ferocity = [];
	public $magicFind = [];
	public $damageAbility = [];
	public $breakTimes = [];
	public $cooldown = [];
	public $add = [];
	public $addStrength = [];
	public $minusSpeed = [];
	public $dataBase;	
	private static $instance;

	public function onLoad() :void{
        self::$instance = $this;
	}
	
    public static function getInstance(): PlayerStats{
        return self::$instance;
    }

	public function onEnable() :void{
		$entityfactory = EntityFactory::getInstance();
		$entityfactory->register(DamageIndicator::class, function(World $world, CompoundTag $nbt) : DamageIndicator{
			return new DamageIndicator(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ['DamageIndicator']);
		$this->saveDefaultConfig();
		$this->dataBase = new SQL("mysql");            		
		$this->getScheduler()->scheduleRepeatingTask(new Popup($this), 20);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}	
	
	public function getDatabase(): Database{
        return $this->dataBase;
	}
	
	public function getDataItem(int $id, int $meta = 0, int $count = 1, $tags = null) : Item{
		$class = new ItemFactory();
		return $class->get($id, $meta, $count, $tags);
	}
	
	public function getHealth(Player $player) :float{
		return $this->getDatabase()->getHealth($player);
	}
	
	public function getDefense(Player $player) :float{
		$value = $this->getDatabase()->getDefense($player) + $this->pets[$player->getXuid()]["Defense"] + $this->stats[$player->getXuid()]["Defense"];
		return $value + ($value * ($this->add[$player->getXuid()]/100));
	}
	
	public function getTrueDefense(Player $player) :float{
		return $this->getDatabase()->getTrueDefense($player);	
	}
	public function getStrength(Player $player) :float{
		$value = $this->getDatabase()->getStrength($player) + $this->pets[$player->getXuid()]["Strength"] + $this->stats[$player->getXuid()]["Strength"];
		return $value + ($value * ($this->add[$player->getXuid()]/100)) + $this->addStrength[$player->getXuid()];
	}
	
	public function getSpeed(Player $player) :float{
		$nbt = $player->getInventory()->getItemInHand()->getNamedTag();
		$speed = $this->getDatabase()->getSpeed($player) + $this->pets[$player->getXuid()]["Speed"] + $this->stats[$player->getXuid()]["Speed"];
		$armors = [
		    $player->getArmorInventory()->getHelmet(),
			$player->getArmorInventory()->getChestplate(),
			$player->getArmorInventory()->getLeggings(),
			$player->getArmorInventory()->getBoots()
		];
		foreach($armors as $armor) {
			if($armor->getNamedTag()->getTag("Speed", IntTag::class) != null){
			    $speed += $armor->getNamedTag()->getInt("Speed");
			}
		}
		if($nbt->getTag("Speed", IntTag::class) != null){
			$speed += $nbt->getInt("Speed");
		}
		return $speed + ($speed * ($this->add[$player->getXuid()]/100)) - $this->minusSpeed[$player->getXuid()];
	}
	
	public function getCritChance(Player $player) :float{
		$value = $this->getDatabase()->getCritChance($player) + $this->pets[$player->getXuid()]["CritChance"] + $this->stats[$player->getXuid()]["CritChance"];
		return $value + ($value * ($this->add[$player->getXuid()]/100));
	}
	
	public function getCritDamage(Player $player) :float{
		$value = $this->getDatabase()->getCritDamage($player) + $this->pets[$player->getXuid()]["CritDamage"] + $this->stats[$player->getXuid()]["CritDamage"];
	    return $value + ($value * ($this->add[$player->getXuid()]/100));
	}
	
	public function setHealth(Player $player, $amount){
		return $this->getDatabase()->setHealth($player, $amount);
	}
	
	public function setDefense(Player $player, $amount){
		return $this->getDatabase()->setDefense($player, $amount);
	}
	
	public function setTrueDefense(Player $player, $amount){
		return $this->getDatabase()->setTrueDefense($player, $amount);	
	}
	
	public function setStrength(Player $player, $amount){
		return $this->getDatabase()->setStrength($player, $amount) ;
	}
	
	public function setSpeed(Player $player, $amount){
		return $this->getDatabase()->setSpeed($player, $amount);
	}
	
	public function setCritChance(Player $player, $amount){
		return $this->getDatabase()->setCritChance($player, $amount);
	}
	
	public function setCritDamage(Player $player, $amount){
		return $this->getDatabase()->setCritDamage($player, $amount);
	}
	
	public function setDamageDifferent(Player $player){
		$this->damageAbility[$player->getXuid()] = $player;
	}
	
	public function getIntelligence(Player $player) :int{
		$nbt = $player->getInventory()->getItemInHand()->getNamedTag();
		$intelligence = $this->getDatabase()->getIntelligence($player);
		$armors = [
		    $player->getArmorInventory()->getHelmet(),
			$player->getArmorInventory()->getChestplate(),
			$player->getArmorInventory()->getLeggings(),
			$player->getArmorInventory()->getBoots()
		];
		foreach($armors as $armor) {
			if($armor->getNamedTag()->getTag("Intelligence", IntTag::class) != null){
			    $intelligence += $armor->getNamedTag()->getInt("Intelligence");
			}
		}
		if(!empty($this->pets[$player->getXuid()])){
		    $intelligence += (int) ($this->pets[$player->getXuid()]["Intelligence"] + $this->stats[$player->getXuid()]["Intelligence"]);
	    }
		if($nbt->getTag("Intelligence", IntTag::class) != null){
			$intelligence += $nbt->getInt("Intelligence");
		}
		return (int) $intelligence;
	}
	
	public function getMagicFind(Player $player) :bool{
		$magicFind = $this->stats[$player->getXuid()]["Magicind"];
		$armors = [
		    $player->getArmorInventory()->getHelmet(),
			$player->getArmorInventory()->getChestplate(),
			$player->getArmorInventory()->getLeggings(),
			$player->getArmorInventory()->getBoots()
		];
		foreach($armors as $armor) {
			if($armor->getNamedTag()->getTag("Magicfind", IntTag::class) != null){
			    $magicFind += $armor->getNamedTag()->getInt("Magicfind");
			}
		}
		if(!empty($this->pets[$player->getXuid()])){
		    $magicFind += (int) $this->pets[$player->getXuid()]["Magicind"];
	    }
		if(rand(1, 1000) <= $magicFind){
			return true;
		}
		return false;
	}
	
	public function checkMana(int $mana, Player $player) :bool{
		if($mana > $this->intelligence[$player->getXuid()]){
			$player->sendMessage(TextFormat::BOLD.TextFormat::RED."Bạn không đủ mana để sử dụng cho việc này!");
		    return false;
		}else{
			$this->intelligence[$player->getXuid()] -= $mana;
			return true;
		}		
		return false;
	}
	
	public function checkLevel(Player $player, $addxp){
		$level = $this->getDatabase()->getLevel($player);
		$xplevel = $this->getDatabase()->getXpLevel($player);
		$goal = $level * 5000;
		if($xplevel >= $goal){
			$this->getDatabase()->setLevel($player, (float)($level + 1));
			$this->getDatabase()->setXpLevel($player, 0.0);
			$player->sendTitle(TextFormat::BOLD.TextFormat::AQUA."Bạn đã lên cấp!", "chúc mừng!");
		    $player->sendMessage(TextFormat::BOLD.TextFormat::GOLD."----Chỉ số của bạn được cập nhật----");
			$player->sendMessage(TextFormat::BOLD.TextFormat::RED."+1 Strength");
			$player->sendMessage(TextFormat::BOLD.TextFormat::GREEN."+1 Defense");
			$player->sendMessage(TextFormat::BOLD.TextFormat::BLUE."+1 Crit Damage");
			$player->sendMessage(TextFormat::BOLD.TextFormat::GOLD."-----------Level ".$this->getDatabase()->getLevel($player)."-----------");
		    if($this->getDatabase()->getLevel($player) == 20){
		        $player->sendMessage(TextFormat::BOLD.TextFormat::GREEN."Bạn đã mở khóa câu cá!");
			    $player->sendMessage(TextFormat::BOLD.TextFormat::AQUA."+ 2% tỉ lệ rớt đồ xịn xò khi câu");
			    $player->sendMessage(TextFormat::BOLD.TextFormat::AQUA."+ tỉ lệ câu các món đồ mới như: ");
			    $player->sendMessage(TextFormat::BOLD.TextFormat::GOLD."Jacob Hoe, Yeti Sword");
			}
			$this->setStrength($player, $this->getDatabase()->getStrength($player) + 1);
			$this->setDefense($player, $this->getDatabase()->getDefense($player) + 1);
			$this->setCritDamage($player, $this->getDatabase()->getCritDamage($player) + 1);
		}else{
			$this->getDatabase()->setXpLevel($player, (float)($xplevel + $addxp));
		}
	}
	
	/*public function getTopLevelList(){
        $server = $this->getServer();
		
        $banList = [];
        foreach ($server->getNameBans()->getEntries() as $entry) {
            if ($this->getDatabase()->accountExists($entry->getName())) {
                $banList[] = $entry->getName();
            }
        }
        $ops = [];
        foreach ($server->getOps()->getAll() as $op) {
            if ($this->getDatabase()->accountExists($op)) {
                $ops[] = $op;
            }
        }
		
		$level = (array)$this->getDatabase()->getLevelAll();
        arsort($level);

        $ret = [];

        $n = 1;
        $this->max = ceil((count($level) - count($banList) - (count($ops))) / 5);
        $page = (int)min($this->max, max(1, 10));

        foreach($level as $p => $level){
            $p = strtolower($p);
            if (isset($banList[$p])) continue;
            if (isset($this->ops[$p]) and $this->addOp === false) continue;
            $current = (int)ceil($n / 5);
            if ($current === $page) {
                $ret[$n] = [$p, $level];
            } elseif ($current > $page) {
                break;
            }
            ++$n;
        }
        return $ret;
	}*/
	
	public function getLevel(Player $player) :array{
		$level = $this->getDatabase()->getLevel($player);
		$xplevel = $this->getDatabase()->getXpLevel($player);
		return [$level, $xplevel];
	}
	
	public function setMiningSpeed(Player $player, $amount){
		return $this->getDatabase()->setMiningSpeed($player, $amount);
	}
	
	public function getMiningSpeed(Player $player) :float{
		return $this->getDatabase()->getMiningSpeed($player);
	}
	
	public function setAttackSpeed(Player $player, $amount){
		return $this->getDatabase()->setAttackSpeed($player, $amount);
	}
	
	public function getAttackSpeed(Player $player) :float{
		return $this->getDatabase()->getAttackSpeed($player);
	}
	
	public function getMiningFortune(Player $player) :float{
		return $this->getDataBase()->getMiningFortune($player);
	}
	
	public function setMiningFortune(Player $player, $amount){
		return $this->getDataBase()->setMiningFortune($player, $amount);
	}
	
	public function getFarmingFortune(Player $player){
		return $this->getDataBase()->getFarmingFortune($player);
	}
	
	public function setFarmingFortune(Player $player, $amount){
		return $this->getDataBase()->setFarmingFortune($player, $amount);
	}
	
	public function getForagingFortune(Player $player){
		return $this->getDataBase()->getForagingFortune($player);
	}
	
	public function setForagingFortune(Player $player, $amount){
		return $this->getDataBase()->setForagingFortune($player, $amount);
	}
	
	public function getFerocity(Player $player){
		return $this->getDataBase()->getFerocity($player);
	}
	
	public function setFerocity(Player $player, $amount){
		return $this->getDatabase()->setFerocity($player, $amount);
	}
	
	public function onPlayerJoinEvent(PlayerJoinEvent $event) :void{
		$player = $event->getPlayer();
		$this->getDataBase()->createProfile($player);
		$this->add[$player->getXuid()] = 0;
		$this->addStrength[$player->getXuid()] = 0;
		$this->minusSpeed[$player->getXuid()] = 0;
		$this->pets[$player->getXuid()] = [
		    "Damage" => 0,
			"CritDamage" => 0,
			"CritChance" => 0,
			"Defense" => 0,
			"Strength" => 0,
			"Speed" => 0,
			"Intelligence" => 0,
			"Health" => 0,
			"MiningSpeed" => 0,
			"AttackSpeed" => 0,
			"MiningFortune" => 0,
			"FarmingFortune" => 0,
			"ForagingFortune" => 0,
			"Ferocity" => 0,
			"Magicfind" => 0
		];
		$this->stats[$player->getXuid()] = [
		    "Damage" => 0,
			"CritDamage" => 0,
			"CritChance" => 0,
			"Defense" => 0,
			"Strength" => 0,
			"Speed" => 0,
			"Intelligence" => 0,
			"Health" => 0,
			"MiningSpeed" => 0,
			"AttackSpeed" => 0,
			"MiningFortune" => 0,
			"FarmingFortune" => 0,
			"ForagingFortune" => 0,
			"Ferocity" => 0,
			"Magicfind" => 50
		];
		$this->addIntelligence[$player->getXuid()] = rand(20, 40);
		$this->intelligence[$player->getXuid()] = $this->getIntelligence($player);		
		$this->critChance[$player->getXuid()] = $this->getCritChance($player);
	}
	
	public function getMaxHealthPlayer(Player $player) :int{
		$health = $this->getHealth($player) + $this->pets[$player->getXuid()]["Health"];
		$armors = [
		    $player->getArmorInventory()->getHelmet(),
			$player->getArmorInventory()->getChestplate(),
			$player->getArmorInventory()->getLeggings(),
			$player->getArmorInventory()->getBoots()
		];
		foreach($armors as $armor) {
			if($armor->getNamedTag()->getTag("Health", IntTag::class) != null){
			    $health += $armor->getNamedTag()->getInt("Health");
			}
		}		
		return (int)($health + ($health * ($this->add[$player->getXuid()]/100)));
	}
	
	public function calculateFarmingFortune(Player $player) :int{
		$farmingfortune = (int)(($this->getFarmingFortune($player) + $this->stats[$player->getXuid()]["FarmingFortune"]));
		return $farmingfortune;
	}
	
	public function calculateMiningFortune(Player $player) :int{
		$miningfortune = (int)(($this->getMiningFortune($player) + $this->stats[$player->getXuid()]["MiningFortune"]));
		return $miningfortune;
	}
	
	public function calculateForagingFortune(Player $player) :int{
		$miningfortune = (int)(($this->getForagingFortune($player) + $this->stats[$player->getXuid()]["ForagingFortune"]));
		return $miningfortune;
	}
	
	public function getDamage(Player $player){
		if(empty($this->pets[$player->getXuid()]) or empty($this->stats[$player->getXuid()])) return [0, 0];
		$nbt = $player->getInventory()->getItemInHand()->getNamedTag();
		$armors = [
		    $player->getArmorInventory()->getHelmet(),
			$player->getArmorInventory()->getChestplate(),
			$player->getArmorInventory()->getLeggings(),
			$player->getArmorInventory()->getBoots()
		];
		$armorstrength = 0;
		$armorcritchance = 0;
		$armorcritdamage = 0;
		$armorferocity = 0;
		foreach($armors as $armor) {
			if($armor->getNamedTag()->getTag("Strength", IntTag::class) != null){
			    $armorstrength += $armor->getNamedTag()->getInt("Strength");
			}
			if($armor->getNamedTag()->getTag("Critchance", IntTag::class) != null){
			    $armorcritchance += $armor->getNamedTag()->getInt("Critchance");
			}
			if($armor->getNamedTag()->getTag("Critdamage", IntTag::class) != null){
			    $armorcritdamage += $armor->getNamedTag()->getInt("Critdamage");
			}
			if($armor->getNamedTag()->getTag("Ferocity", IntTag::class) != null){
			    $armorferocity += $armor->getNamedTag()->getInt("Ferocity");
			}
		}
		$additivemultiplier = $armorcritdamage;
		if($nbt->getTag("Critdamage", IntTag::class) != null){
			$additivemultiplier += $nbt->getInt("Critdamage");
		}
		$critChance = $armorcritchance;
		if($nbt->getTag("Critchance", IntTag::class) != null){
			$critChance += $nbt->getInt("Critchance");
		}
		$weaponDamage = 0;
        if($nbt->getTag("Damagemodifier", IntTag::class) != null){                   					
			$weaponDamage = $nbt->getInt("Damagemodifier");			
		} 
		$weaponFerocity = $armorferocity;
        if($nbt->getTag("Ferocity", IntTag::class) != null){                   					
			$weaponFerocity += $nbt->getInt("Ferocity");			
		} 
		$weaponStrength = $armorstrength;
        if($nbt->getTag("Strength", IntTag::class) != null){                   					
			$weaponStrength += $nbt->getInt("Strength");			
		} 
	    $haveCrit = false;
		$chance = (int)$this->getCritChance($player) + $critChance;
		$critDamage = 0;
		if($chance < 100){
			$random = rand(0, 100);
			if($random <= $chance){
				$critDamage = $this->getCritDamage($player);
				$haveCrit = true;
			}
		}else{
		    $critDamage = $this->getCritDamage($player);
			$haveCrit = true;
		}
	    $damage = (5 + $weaponDamage) * (1 + ($weaponStrength + $this->getStrength($player)) / 100) * (1 + $critDamage / 100) * (1 + $additivemultiplier / 100);
		$randomchance = rand(1, 100);
		if($weaponFerocity != 0 and $weaponFerocity <= 100){			
			if(50 <= $randomchance){
				$damage = $damage * 2;
				$player->getWorld()->addSound($player->getLocation()->asVector3(), new PotionSplashSound(), $player->getWorld()->getPlayers());
			}
		}
		if($weaponFerocity != 0 and $weaponFerocity >= 100){
			$add = $weaponFerocity/10;
			if(50 <= $randomchance + $add){
				$damage = $damage * 3;
				$player->getWorld()->addSound($player->getLocation()->asVector3(), new PotionSplashSound(), $player->getWorld()->getPlayers());
			}else{
				$damage = $damage * 4;
				$player->getWorld()->addSound($player->getLocation()->asVector3(), new PotionSplashSound(), $player->getWorld()->getPlayers());
			}
		}
		return [$damage, $haveCrit];
	}
	
	public function getdamageAbility(Player $player, $entity, int $mana){
		if($entity instanceof NPCEntity) return;
		$this->damageAbility[$player->getXuid()] = $player;
		$nbt = $player->getInventory()->getItemInHand()->getNamedTag();
		$armors = [
		    $player->getArmorInventory()->getHelmet(),
			$player->getArmorInventory()->getChestplate(),
			$player->getArmorInventory()->getLeggings(),
			$player->getArmorInventory()->getBoots()
		];
		$damage = $this->getIntelligence($player);
		$damagePercent = 0;
		foreach($armors as $armor) {
			if($armor->getNamedTag()->getTag("Intelligence", IntTag::class) != null){
			    $damage += $armor->getNamedTag()->getInt("Intelligence");
			}
			if($armor->getNamedTag()->getTag("Abilitydamage", IntTag::class) != null){
			    $damagePercent += $armor->getNamedTag()->getInt("Abilitydamage") / 100;
			}
		}
		$damage = (($damage * 1.2) + ($damage * $damagePercent)) * $mana;		
        $nbt = $this->createBaseNBT($entity->getPosition()->add(0, $player->getEyeHeight() + 1, 0));
		$entityDamage = new DamageIndicator($entity->getLocation(), $nbt);                	
        $entityDamage->setInvisible(false);
        $entityDamage->setScale(0.01);
        $entityDamage->setNametag(TextFormat::GRAY."".number_format($damage));					
	    $entityDamage->setNameTagVisible(true);
        $entityDamage->setNameTagAlwaysVisible(true);				
        $entityDamage->spawnToAll();
		$this->getScheduler()->scheduleDelayedTask(new Damage($this, $player, $entityDamage), 20);			
		return $damage;
	}
	
	public function getDefensePlayer(Player $player) :int{		
		$armors = [
		    $player->getArmorInventory()->getHelmet(),
			$player->getArmorInventory()->getChestplate(),
			$player->getArmorInventory()->getLeggings(),
			$player->getArmorInventory()->getBoots()
		];
		$trueDenfense = 0;
		foreach($armors as $armor) {
			$trueDenfense += $armor->getDefensePoints();
		}
		$enchantDefense = 0;
		/*foreach($armors as $armorItem) {			
		    foreach($armorItem->getEnchantments() as $enchantment){
		        if($enchantment->getId() === Enchantment::PROTECTION){
					$enchantDefense += $enchantment->getLevel() * 2;					
				}				
			}		
		}*/        		
		$defenseArmor = 0;
		foreach($armors as $armor){
			$nbt = $armor->getNamedTag();
			if($nbt->getTag("Defense", IntTag::class) != null){
				$defenseArmor += $nbt->getInt("Defense");
			}
		}
		$nbt = $player->getInventory()->getItemInHand()->getNamedTag();
		if($nbt->getTag("Defense", IntTag::class) != null){
			$defenseArmor += $nbt->getInt("Defense");
		}
	    $defense = (($trueDenfense + $enchantDefense) * 50) + (($defenseArmor + $this->getDefense($player)));    		
		return (int)$defense;		
	}
	
	public function onDeath(PlayerDeathEvent $event){
		$player = $event->getPlayer();			
		unset($this->speed[$player->getXuid()]);
		$event->setDeathMessage("");
	}
	
	public function onDamage(EntityDamageEvent $event) :void{
		$entity = $event->getEntity();
        if(!$event->isCancelled() and !($entity instanceof Bubble) and !($entity instanceof Throww) and !($entity instanceof ShapePet) and $event->isCancelled() === false and $event instanceof EntityDamageByEntityEvent){          
			$damager = $event->getDamager();
			if($damager instanceof Player){ //and !$entity instanceof Player){
				if(isset($this->damageAbility[$damager->getXuid()])){
					unset($this->damageAbility[$damager->getXuid()]);
					return;				
				}
				$damage = $this->getDamage($damager);
				$nbt = $this->createBaseNBT($entity->getPosition()->add(0, $damager->getEyeHeight() + 1, 0));
				$entityDamage = new DamageIndicator($entity->getLocation(), $nbt);                	
                $entityDamage->setInvisible(false);
                $entityDamage->setScale(0.01);								
                if($damage[1] == true){
					$i = 0;
					$color = ["§f", "§e", "§6", "§c"];
					$text = str_split((string)number_format(($damage[0] + (int)$event->getBaseDamage())));
					foreach($text as $case => $number){				
						if($i <= 3){
						    $text[$case] = $color[$i].$number;							
						}else{
							$i = 0;
							$text[$case] = $color[$i].$number;
						}
						$i++;
					}
					$entityDamage->setNametag(TextFormat::BOLD.TextFormat::RED."✧".implode("", $text)."✧");
				}else{
					$entityDamage->setNametag(TextFormat::GRAY."".number_format($damage[0] + (int)$event->getBaseDamage()));					
				}
				$entityDamage->setNameTagVisible(true);
                $entityDamage->setNameTagAlwaysVisible(true);				
                $entityDamage->spawnToAll();	                								    
				if($entity instanceof Player){
					$event = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_MAGIC, $damage[0]/45);
				}else{
					$event = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_MAGIC, $damage[0]);
				}
				$entity->attack($event);
				$this->getScheduler()->scheduleDelayedTask(new Damage($this, $damager, $entityDamage), 20);
			}
		}
		$attackspeed = 0;		
		if($event instanceof EntityDamageByEntityEvent){
			$damager = $event->getDamager();
			if($damager instanceof Player){
			    $nbt = $damager->getInventory()->getItemInHand()->getNamedTag();
		        if($nbt->getTag("Attackspeed", IntTag::class) != null){
			        $attackspeed = $nbt->getInt("Attackspeed");
				}
			    if(!empty($this->stats[$damager->getXuid()])){
				    $speed = (int)(1 + $this->stats[$damager->getXuid()]["AttackSpeed"] + $this->getAttackSpeed($damager) + $attackspeed);
					$calcualteattackspeed = (int) 100/$speed;
			        if($speed <= 100){
					    $event->setAttackCooldown($calcualteattackspeed);
					}else{
						$event->setAttackCooldown(10);
					}
				}
			}
		}
	}
	
	public function onDefense(EntityDamageEvent $event) :void{
        $entity = $event->getEntity();
		if($event->getCause() != EntityDamageEvent::CAUSE_VOID){
		    if(!$event->isCancelled() and $entity instanceof Player){
			    if(!isset($this->pets[$entity->getXuid()])) return;
			    $entity->heal(new EntityRegainHealthEvent($entity, (($this->getDefensePlayer($entity)) / 100), EntityRegainHealthEvent::CAUSE_REGEN));
			}
		}
	}
	
	public function onBreak(BlockBreakEvent $event) :void{
		$player = $event->getPlayer();
		$block = $event->getBlock();       		
		$vector3 = new Vector3($block->getPosition()->x, $block->getPosition()->y + 1, $block->getPosition()->z);	
		$nbt = $player->getInventory()->getItemInHand()->getNamedTag();
		$farmingFortune = $this->calculateFarmingFortune($player);		
		$foragingFortune = $this->calculateForagingFortune($player);
		$miningFortune = $this->calculateMiningFortune($player);
		if($player->isCreative() == true){
			return;
		}
		if($nbt->getTag("Farmingfortune", IntTag::class) != null){
			$farmingFortune += (int)$nbt->getInt("Farmingfortune");
		}
		if($nbt->getTag("Foragingfortune", IntTag::class) != null){
			$foragingFortune += (int)$nbt->getInt("Foragingfortune");
		}			
	    if($nbt->getTag("Miningfortune", IntTag::class) != null){
			$miningFortune += (int)$nbt->getInt("Miningfortune");
		}
		/*$speed = $this->stats[$player->getXuid()]["MiningSpeed"] + $this->getMiningSpeed($player) + $timeadd;		
        if(!$e->getInstaBreak()){
			do{
				if(!isset($this->breakTimes[$uuid = $p->getRawUniqueId()])){
					$event->setCancelled();
					break;
				}
				$target = $e->getBlock();
				$item = $e->getItem();
				$expectedTime = ceil($target->getBreakTime($item) * 20);
				if($p->hasEffect(Effect::HASTE)){
					$expectedTime *= 1 - (0.2 * $p->getEffect(Effect::HASTE)->getEffectLevel());
				}
				if($p->hasEffect(Effect::MINING_FATIGUE)){
					$expectedTime *= 1 + (0.3 * $p->getEffect(Effect::MINING_FATIGUE)->getEffectLevel());
				}
				if($nbt->getTag("Miningspeed", IntTag::class) != null){
			        $expectedTime *= 1 - (0.2 * $nbt->getInt("Miningspeed"));
				}
				$expectedTime -= 1;
				$actualTime = ceil(microtime(true) * 20) - $this->breakTimes[$uuid = $p->getRawUniqueId()];
				if($actualTime < $expectedTime){
					$event->setCancelled(true);                 
					break;
				}else{
					
				}
				unset($this->breakTimes[$uuid]);
			} while(false);
		}*/
		$seeds = [83 => false, 59 => 7,  141 => 7, 142 => 7, 103 => false, 86 => 3, 81 => false];
		$lumber = [17 => 0, 162 => 0];
		$blocks = [14 => [266, 0], 56 => [264, 0], 21 => [351, 4], 73 => [331, 0], 16 => [263, 0], 129 => [388, 0], 1 => [4, 0]];
		if(!$event->isCancelled()){
			foreach($seeds as $id => $meta){
				if($block->getId() == $id){
					if($meta != false){
						if($block->getMeta() != $meta){
							return;
						}
					}
					$dropSeed = $event->getDrops();
					if($block->getId() == 86){
						if(rand(1, 2000) <= $farmingFortune){
							$dropSeed[] = $this->getDataItem(86, 0, rand(1, 15));
						    $event->setDrops($dropSeed);
						}
					}
					if($block->getId() == 81){
						if(rand(1, 2000) <= $farmingFortune){
						    $dropSeed[] = $this->getDataItem(81, 0, rand(1, 15));
						    $event->setDrops($dropSeed);
						}
					}
					if($block->getId() == 103){
						if(rand(1, 2000) <= $farmingFortune){
						    $dropSeed[] = $this->getDataItem(103, 0, rand(1, 15));
						    $event->setDrops($dropSeed);
						}
					}
					if($block->getId() == 142){
						if(rand(1, 2000) <= $farmingFortune){
						    $dropSeed[] = $this->getDataItem(142, 0, rand(1, 15));
						    $event->setDrops($dropSeed);
						}
					}
					if($block->getId() == 141){
						if(rand(1, 2000) <= $farmingFortune){
							$dropSeed[] = $this->getDataItem(141, 0, rand(1, 15));
						    $event->setDrops($dropSeed);
						}
					}
					if($block->getId() == 59){
						if(rand(1, 2000) <= $farmingFortune){
						    $dropSeed[] = $this->getDataItem(295, 0, rand(1, 4));
							$dropSeed[] = $this->getDataItem(296, 0, rand(1, 15));
						    $event->setDrops($dropSeed);
						}
					}
					if($block->getId() == 115){
						if(rand(1, 2000) <= $farmingFortune){
						    $dropSeed[] = $this->getDataItem(115, 0, rand(1, 15));
						    $event->setDrops($dropSeed);
						}
					}
					if($block->getId() == 83){
						if(rand(1, 2000) <= $farmingFortune){
						    $dropSeed[] = $this->getDataItem(338, 0, rand(1, 15));
						    $event->setDrops($dropSeed);
						}
					}
				}
			}		
			foreach($lumber as $id => $meta){
				if($block->getId() == $id){
					if(rand(1, 2000) <= $foragingFortune){
					    $player->getWorld()->dropItem($vector3, $this->getDataItem($id, $block->getMeta(), rand(1, 15)));
					}
				}
			}
			foreach($blocks as $id => [$idd, $meta]){
				if($block->getId() == $id){
					if(rand(1, 2000) <= $miningFortune){
					    $player->getWorld()->dropItem($vector3, $this->getDataItem($idd, $meta, rand(1, 15)));
					}
				}
			}
		}
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
}