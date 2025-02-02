<?php

namespace hachkingtohach1\FCCore;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\inventory\Inventory;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\entity\effect\{Effect, EffectInstance, StringToEffectParser};
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket; 
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\world\sound\AnvilFallSound;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityTrampleFarmlandEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\entity\Location;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\block\{Block, BlockFactory};
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use skymin\InventoryLib\{InvLibManager, LibInvType, InvLibAction, LibInventory};
use hachkingtohach1\PlayerStats\PlayerStats;
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
use leinne\pureentities\entity\other\God;
use leinne\pureentities\entity\other\SpecialEnderman;
use leinne\pureentities\entity\other\SpecialGolem;
use leinne\pureentities\entity\dungeons\BossFloorOne;
use leinne\pureentities\entity\dungeons\DungeonEnderman;
use leinne\pureentities\entity\dungeons\DungeonSkeleton;
use leinne\pureentities\entity\dungeons\DungeonZombie;
use leinne\pureentities\entity\dungeons\BossFloorTwo;
use leinne\pureentities\entity\dungeons\DungeonPig;
use leinne\pureentities\entity\dungeons\DungeonIronZombie;
use leinne\pureentities\entity\dungeons\DungeonDrowned;
use leinne\pureentities\entity\vehicle\Boat;
use pocketmine\entity\{EntityFactory, EntityDataHelper};
use pocketmine\block\Water;
use hachkingtohach1\MyItem\MyItem;
use hachkingtohach1\Dungeon\Dungeon;
use Vecnavium\SkyBlocksPM\SkyBlocksPM;
use _64FF00\PurePerms\PurePerms;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\math\Math;
use function array_key_exists;
use function array_keys;
use function implode;
use function is_numeric;
use function json_decode;
use function microtime;

class FCCore extends PluginBase implements Listener {
	
	private const SURVIVAL = false;
	private const WORLD_SURVIVAL = null;
	private const TAG_BUILDER_WAND = "Builderwand";
	public $hasJoined = [];
	private $inSetUp = [];
	private $inCombat = [];
	private $addCoins = [];
	private $blockBreak = [];
	private $placeBlock = [];
	private $breakBlock = [];
	private $party = [];
	private $invite = [];
	private $banPlayer = [];
	private static $instance = null;
	
	public function onLoad() :void{
        self::$instance = $this;
	}
	
	public static function getInstance(): FCCore{
        return self::$instance;
    }

    public function onEnable() :void{
		InvLibManager::register($this);	
        $this->getServer()->getNetwork()->setName(TextFormat::BOLD.TextFormat::RED."F".TextFormat::GOLD."C".TextFormat::RESET.":".TextFormat::GREEN . " Skyblock VN!");		
		$this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
	
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
		if($command->getName() == "hub"){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED."This is command for in-game!");
				return false;
			}
			$sender->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
			return true;
        }
        if($command->getName() == "blockpos"){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED."This is command for in-game!");
				return false;
			}
			if(!$this->getServer()->isOp($sender->getName())){
				$sender->sendMessage(TextFormat::RED."This is command for admin!");
				return false;
			}
			$this->inSetUp[$sender->getName()] = $sender;
			$sender->sendMessage(TextFormat::GREEN."Break one block!");
			return true;
        }		
        if($command->getName() == "joinsurvival"){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED."This is command for in-game!");
				return false;
			}						
			if(self::WORLD_SURVIVAL != null){
				if(!$this->getServer()->getWorldManager()->isWorldLoaded(self::WORLD_SURVIVAL)) {
                    $this->getServer()->getWorldManager()->loadWorld(self::WORLD_SURVIVAL);
				}
				$world = $this->getServer()->getWorldManager()->getWorldByName(self::WORLD_SURVIVAL);
				$sender->teleport(Position::fromObject($world->getSpawnLocation()->add(rand(1, 100), 10, rand(1, 100)), $world));
				$sender->sendMessage(TextFormat::GREEN."You are playing FoxCraft!");
			}else{
				$sender->sendMessage(TextFormat::RED."Something didn't go OK! ".TextFormat::GRAY."WORLD_ERROR");
			}
			return true;
        }	
        if($command->getName() == "mystats"){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED."This is command for in-game!");
				return false;
			}
			$nbt = $sender->getInventory()->getItemInHand()->getNamedTag();
			$critDamage = PlayerStats::getInstance()->getCritDamage($sender);
			$critChance = PlayerStats::getInstance()->getCritChance($sender);
			$defense = PlayerStats::getInstance()->getDefensePlayer($sender);
			$strength = PlayerStats::getInstance()->getStrength($sender);
			$speed = PlayerStats::getInstance()->getSpeed($sender);
			$intelligence = PlayerStats::getInstance()->getIntelligence($sender);
			$miningSpeed = PlayerStats::getInstance()->getMiningSpeed($sender);
			$attackSpeed = PlayerStats::getInstance()->getAttackSpeed($sender);
			$miningFortune = PlayerStats::getInstance()->getMiningFortune($sender);
			$farmingFortune = PlayerStats::getInstance()->getFarmingFortune($sender);
			$foragingFortune = PlayerStats::getInstance()->getForagingFortune($sender);
			$ferocity = PlayerStats::getInstance()->getFerocity($sender);
			$armors = [
		        $sender->getArmorInventory()->getHelmet(),
			    $sender->getArmorInventory()->getChestplate(),
			    $sender->getArmorInventory()->getLeggings(),
			    $sender->getArmorInventory()->getBoots()
		    ];
		    foreach($armors as $armor){
				if($armor->getNamedTag()->getTag("Strength", IntTag::class) != null){
			    	$strength += $armor->getNamedTag()->getInt("Strength");
				}
				if($armor->getNamedTag()->getTag("Critchance", IntTag::class) != null){
			    	$critChance += $armor->getNamedTag()->getInt("Critchance");
				}
				if($armor->getNamedTag()->getTag("Critdamage", IntTag::class) != null){
			    	$critDamage += $armor->getNamedTag()->getInt("Critdamage");
				}
				if($armor->getNamedTag()->getTag("Ferocity", IntTag::class) != null){
			    	$ferocity += $armor->getNamedTag()->getInt("Ferocity");
				}			
			}
			if($nbt->getTag("Critdamage", IntTag::class) != null){
				$critDamage += $nbt->getInt("Critdamage");
			}
			if($nbt->getTag("Critchance", IntTag::class) != null){
				$critChance += $nbt->getInt("Critchance");
			}
        	if($nbt->getTag("Ferocity", IntTag::class) != null){                   					
				$ferocity += $nbt->getInt("Ferocity");			
			} 
        	if($nbt->getTag("Strength", IntTag::class) != null){                   					
				$strength += $nbt->getInt("Strength");			
			}
            if($nbt->getTag("Attackspeed", IntTag::class) != null){                   					
				$attackSpeed += $nbt->getInt("Attackspeed");			
			}	
            if($nbt->getTag("Miningspeed", IntTag::class) != null){                   					
				$miningSpeed += $nbt->getInt("Miningspeed");			
			}
            if($nbt->getTag("Miningfortune", IntTag::class) != null){                   					
				$miningFortune += $nbt->getInt("Miningfortune");			
			}
            if($nbt->getTag("Farmingfortune", IntTag::class) != null){                   					
				$farmingFortune += $nbt->getInt("Farmingfortune");			
			}
            if($nbt->getTag("Foragingfortune", IntTag::class) != null){                   					
				$foragingFortune += $nbt->getInt("Foragingfortune");			
			}			
			$sender->sendMessage(TextFormat::YELLOW."----MyStats----");
			$sender->sendMessage(TextFormat::BOLD.TextFormat::RED."✺ Strength".TextFormat::GRAY." | ".TextFormat::WHITE.$strength);
			$sender->sendMessage(TextFormat::BOLD.TextFormat::BLUE."乂 Crit Damage".TextFormat::GRAY." | ".TextFormat::WHITE.$critDamage);
			$sender->sendMessage(TextFormat::BOLD.TextFormat::BLUE."⅒ Crit Chance".TextFormat::GRAY." | ".TextFormat::WHITE.$critChance);
			$sender->sendMessage(TextFormat::BOLD.TextFormat::YELLOW."ミ Attack Speed".TextFormat::GRAY." | ".TextFormat::WHITE.$attackSpeed);
			$sender->sendMessage(TextFormat::BOLD.TextFormat::WHITE."彡 Speed".TextFormat::GRAY." | ".TextFormat::WHITE.$speed);
			$sender->sendMessage(TextFormat::BOLD.TextFormat::AQUA."✎ Intelligence".TextFormat::GRAY." | ".TextFormat::WHITE.$intelligence);
			$sender->sendMessage(TextFormat::BOLD.TextFormat::GREEN."❈ Defense".TextFormat::GRAY." | ".TextFormat::WHITE.$defense);
			//$sender->sendMessage(TextFormat::BOLD.TextFormat::GOLD."ミ Mining Speed".TextFormat::GRAY." | ".TextFormat::WHITE.$miningSpeed);
			$sender->sendMessage(TextFormat::BOLD.TextFormat::GOLD."☘ Mining Fortune".TextFormat::GRAY." | ".TextFormat::WHITE.$miningFortune);
			$sender->sendMessage(TextFormat::BOLD.TextFormat::GOLD."☘ Farming Fortune".TextFormat::GRAY." | ".TextFormat::WHITE.$farmingFortune);
			$sender->sendMessage(TextFormat::BOLD.TextFormat::GOLD."☘ Foraging Fortune".TextFormat::GRAY." | ".TextFormat::WHITE.$foragingFortune);
			$sender->sendMessage(TextFormat::BOLD.TextFormat::RED."❃ Ferocity".TextFormat::GRAY." | ".TextFormat::WHITE.$ferocity);
			$sender->sendMessage(TextFormat::YELLOW."--------------");
			return true;
        }
		if($command->getName() == "info"){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED."This is command for in-game!");
				return false;
			}
			$this->mainForm($sender);
			return true;
		}
        if($command->getName() == "summon"){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED."This is command for in-game!");
				return false;
			}
			if(!$this->getServer()->isOp($sender->getName())){
				$sender->sendMessage(TextFormat::RED."This is command for admin!");
				return false;
			}
			if(!isset($args[0]) or !isset($args[1]) or !is_numeric((int)$args[1])){
				$sender->sendMessage(TextFormat::GREEN."/summon <name> <amount>");
				return false;
			}
			$radX = mt_rand(3, 10);
            $radZ = mt_rand(3, 10);
			$pos = $sender->getLocation()->floor();
			$pos->y = $sender->getWorld()->getHighestBlockAt($pos->x += mt_rand(0, 1) ? $radX : -$radX, $pos->z += mt_rand(0, 1) ? $radZ : -$radZ) + 1;
			$entityClasses = [
                "cow" => Cow::class, 
				"pig" => Pig::class, 
				"sheep" => Sheep::class, 
				"chicken" => Chicken::class, 
				"mooshroom" => Mooshroom::class, 
				"iron golem" => IronGolem::class,
                "zombie" => Zombie::class, 
				"creeper" => Creeper::class, 
				"skeleton" => Skeleton::class, 
				"spider" => Spider::class, 
				"zombified piglin" => ZombifiedPiglin::class,
				"god" => God::class,
				"specialenderman" => SpecialEnderman::class,
				"specialgolem" => SpecialGolem::class,
				"bossfloortwo" => BossFloorTwo::class
            ];			
			if(empty($entityClasses[strtolower($args[0])])){
				$sender->sendMessage(TextFormat::RED."Entity non-exist!");
				return false;
			}
			$count = (int) $args[1];
			for($i = 1; $i <= $count; $i++){			
                $entity = new $entityClasses[strtolower($args[0])](Location::fromObject($pos, $sender->getWorld()));
                $entity->spawnToAll();				
			}
			$sender->sendMessage(TextFormat::GREEN.$args[1]." entity has spawned!");
			return true;
        }
		if($command->getName() == "huongdan"){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED."This is command for in-game!");
				return false;
			}		
			$this->mainHelp($sender);
			return true;
		}
		if($command->getName() == "warp"){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED."This is command for in-game!");
				return false;
			}		
			$this->mainFormWarp($sender);
			return true;
		}
		if($command->getName() == "tpa"){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED."This is command for in-game!");
				return false;
			}
            $pure = $this->getServer()->getPluginManager()->getPlugin('PurePerms');
            $userGroup = $pure->getUserDataMgr()->getGroup($sender);
            if($userGroup != "Vip++"){
				$sender->sendMessage(TextFormat::RED."Bạn phải là VIP++ mới sử dụng được lệnh này!");
				return false;
			}				
			if(!isset($args[0])){
				$sender->sendMessage(TextFormat::GREEN."/tpa < tên >");
				return false;
			}
			$playerName = strtolower($args[0]);
			$targetPlayer = $this->getServer()->getPlayerByPrefix($playerName);
			if($this->getServer()->isOp($targetPlayer->getName())){
				$sender->sendMessage(TextFormat::RED."Đây là admin nên bạn không thể dịch chuyển đến họ!");
				return false;
			}
			if($targetPlayer != null){
				$sender->teleport($targetPlayer->getLocation());
			}else{
				$sender->sendMessage(TextFormat::RED."Không tìm thấy người chơi này!");
			}
			return true;
		}
		return false;
	}
	
	public function mainUpdate(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
	    $form = $api->createSimpleForm(function (Player $player, int $data = null){
		    $result = $data;
		    if($result === null){
			    return true;
			}
		    switch($result){							
                default: break;					
			}
		});
		$form->setTitle("Cập nhật");
		$txt = 
		    "+ Thêm VIP+\n\n". 
			"+ Thêm VIP++\n\n".
			"+ Thêm RandomOre\n\n"
        ;			
		$form->setContent($txt);
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function mainHelp(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
	    $form = $api->createSimpleForm(function (Player $player, int $data = null){
		    $result = $data;
		    if($result === null){
			    return true;
			}
		    switch($result){							
                default: break;					
			}
		});
		$form->setTitle("Hướng dẫn chơi");
		$txt = 
		    "§l§cHướng dẫn cơ bản:\n\n". 
            "§l§f- Chào mừng đến với sever §cFoxcraft§f, đây là một số cách chơi cơ bản\n\n". 
            "§l§f- Ấn nút di chuyển để di chuyển\n\n". 		
            "§l§f- Ấn nhảy để thực hiện thao tác nhảy\n\n". 		
            "§l§f- Di chuyển đến cổng The End\n\n". 		
            "§l§f\n\n".
			"§l§c- Có các tính năng sẽ được nói vào mục sau:\n\n".
            "§l§f+ Về các lệnh cơ bản sẽ được nói trong mục > Các lệnh cơ bản <\n\n". 	
            "§l§f+ Về cách để bán vật phải thành Coin sẽ có trong mục > Bán Vật Phẩm <\n\n". 		
            "§l§f+ Về việc tham gia nhiệm vụ sẽ được chỉ dẫn qua mục > Nhiệm vụ <\n\n". 		
            "§l§f+ Về cách chơi Dungeon sẽ được hướng dẫn qua > Dungeon <\n\n". 		
            "§l§f+ Về cách chế tạo vũ khí đặc biệt sẽ được chỉ qua mục > Chế tạo <\n\n". 		
            "§l§f\n\n". 
			"§l§cCác lệnh cơ bản:\n\n". 
            "§l§f- §b/hub §fđể trở về sảnh chính\n\n".           	
            "§l§f- §b/ah §fđể tham gia chợ đen\n\n". 		
            "§l§f- §b/kill §fđể giết chính mình\n\n"
        ;			
		$form->setContent($txt);
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function mainFormWarp(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
	    $form = $api->createSimpleForm(function (Player $player, int $data = null){
		    $result = $data;
		    if($result === null){
			    return true;
			}
			$levelPlayer = PlayerStats::getInstance()->getLevel($player)[0];
		    switch($result){				
                case "0";	
				    if((int) $levelPlayer < 2){
						$player->sendMessage("§l§cBạn phải level 2 để tham gia");
						break;
					}
				    $name = "Coal";
                    if(!$this->getServer()->getWorldManager()->isWorldLoaded($name)){
                        $this->getServer()->getWorldManager()->loadWorld($name);
				    }
				    $world = $this->getServer()->getWorldManager()->getWorldByName($name);
				    $player->teleport(Position::fromObject($world->getSpawnLocation(), $world));					
			    break;		
                case "1":
				    if((int) $levelPlayer < 3){
						$player->sendMessage("§l§cBạn phải level 3 để tham gia");
						break;
					}
				    $name = "Iron";
                    if(!$this->getServer()->getWorldManager()->isWorldLoaded($name)){
                        $this->getServer()->getWorldManager()->loadWorld($name);
				    }
				    $world = $this->getServer()->getWorldManager()->getWorldByName($name);
				    $player->teleport(Position::fromObject($world->getSpawnLocation(), $world));			
                break;
                case "2":
				    if((int) $levelPlayer < 4){
						$player->sendMessage("§l§cBạn phải level 4 để tham gia");
						break;
					}
				    $name = "Gold";
                    if(!$this->getServer()->getWorldManager()->isWorldLoaded($name)){
                        $this->getServer()->getWorldManager()->loadWorld($name);
				    }
				    $world = $this->getServer()->getWorldManager()->getWorldByName($name);
				    $player->teleport(Position::fromObject($world->getSpawnLocation(), $world));			
                break;
                case "3":
				    if((int) $levelPlayer < 5){
						$player->sendMessage("§l§cBạn phải level 5 để tham gia");
						break;
					}
				    $name = "Redstone";
                    if(!$this->getServer()->getWorldManager()->isWorldLoaded($name)){
                        $this->getServer()->getWorldManager()->loadWorld($name);
				    }
				    $world = $this->getServer()->getWorldManager()->getWorldByName($name);
				    $player->teleport(Position::fromObject($world->getSpawnLocation(), $world));			
                break;
                case "4":
				    if((int) $levelPlayer < 6){
						$player->sendMessage("§l§cBạn phải level 6 để tham gia");
						break;
					}
				    $name = "Lapis";
                    if(!$this->getServer()->getWorldManager()->isWorldLoaded($name)){
                        $this->getServer()->getWorldManager()->loadWorld($name);
				    }
				    $world = $this->getServer()->getWorldManager()->getWorldByName($name);
				    $player->teleport(Position::fromObject($world->getSpawnLocation(), $world));			
                break; 
                case "5":
				    if((int) $levelPlayer < 7){
						$player->sendMessage("§l§cBạn phải level 7 để tham gia");
						break;
					}
				    $name = "Diamond";
                    if(!$this->getServer()->getWorldManager()->isWorldLoaded($name)){
                        $this->getServer()->getWorldManager()->loadWorld($name);
				    }
				    $world = $this->getServer()->getWorldManager()->getWorldByName($name);
				    $player->teleport(Position::fromObject($world->getSpawnLocation(), $world));			
                break;
                case "6":
				    if((int) $levelPlayer < 8){
						$player->sendMessage("§l§cBạn phải level 8 để tham gia");
						break;
					}
				    $name = "Obisidian";
                    if(!$this->getServer()->getWorldManager()->isWorldLoaded($name)){
                        $this->getServer()->getWorldManager()->loadWorld($name);
				    }
				    $world = $this->getServer()->getWorldManager()->getWorldByName($name);
				    $player->teleport(Position::fromObject($world->getSpawnLocation(), $world));			
                break;	
                case "7":				    
					$name = "kitpvp3latinplaynet";
                    if(!$this->getServer()->getWorldManager()->isWorldLoaded($name)){
                        $this->getServer()->getWorldManager()->loadWorld($name);
				    }
				    $world = $this->getServer()->getWorldManager()->getWorldByName($name);
				    $player->teleport(Position::fromObject($world->getSpawnLocation(), $world));			
                break;				
                default: break;					
			}
		});
		$form->setTitle("§l§aWarp");
		$txt = "§l• §aCảm ơn bạn đã ủng hộ server!";
		$form->setContent($txt);
		$form->addButton("§l§cCoal",0,"");
		$form->addButton("§l§cIron",0,"");
		$form->addButton("§l§cGold",0,"");
		$form->addButton("§l§cRedstone",0,"");
		$form->addButton("§l§cLapis",0,"");
		$form->addButton("§l§cDiamond",0,"");
		$form->addButton("§l§cObsidian",0,"");
		$form->addButton("§l§cPVP",0,"");
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function mainForm(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
	    $form = $api->createSimpleForm(function (Player $player, int $data = null){
		    $result = $data;
		    if($result === null){
			    return true;
			}
		    switch($result){				
                case "0";	
                    $this->formPremiumCase($player);						
			    break;		
                case "1":
				    Server::getInstance()->dispatchCommand($player, "napthe");
                break;
                case "2":
				    $pure = $this->getServer()->getPluginManager()->getPlugin('PurePerms');
                    $userGroup = $pure->getUserDataMgr()->getGroup($player);    					
                    if($userGroup != "Vip" and $userGroup != "Vip+" and $userGroup != "Vip++"){
					    $this->formVip($player);
					}
					if($userGroup == "Vip" and $userGroup != "Vip+" and $userGroup != "Vip++"){
						$this->formVipAdd($player);
					}
					if($userGroup != "Vip" and $userGroup == "Vip+" and $userGroup != "Vip++"){
						$this->formVipAddAdd($player);
					}
					if($userGroup == "Vip++"){
						$player->sendMessage("§l§bBạn đã up full rank của server! chúng tôi xin trân trọng cảm ơn!");
					}
                break;	
                case "3":
				    Server::getInstance()->dispatchCommand($player, "reward");
                break;		
				case "4":
				    $this->formTool($player);
                break;
                default: break;					
			}
		});
		$form->setTitle("§l§2Info");
		$txt = "§l• §aCảm ơn bạn đã ủng hộ server!";
		$form->setContent($txt);
		$form->addButton("§l§6Premium Case",0,"");
		$form->addButton("§l§cNạp tiền",0,"");
		$form->addButton("§l§cNâng cấp rank",0,"");
		$form->addButton("§l§cĐiểm danh hằng ngày",0,"");
		$form->addButton("§l§cMua dụng cụ",0,"");
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function menuForm(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
	    $form = $api->createSimpleForm(function (Player $player, int $data = null){
		    $result = $data;
		    if($result === null){
			    return true;
			}
		    switch($result){				
                case "0";	
                    Server::getInstance()->dispatchCommand($player, "mystats");					
			    break;		
                case "1":
				    Server::getInstance()->dispatchCommand($player, "reward");
                break;	
				case "2":
				    Server::getInstance()->dispatchCommand($player, "hub");
                break;
				case "3":
				    Server::getInstance()->dispatchCommand($player, "ah");
                break;
                case "4":
				    Server::getInstance()->dispatchCommand($player, "warp");
                break;
				case "5":
				    $this->menuFormSB($player);
				break;
                case "6":
				    Server::getInstance()->dispatchCommand($player, "st");
                break;			
                default: break;					
			}
		});
		$form->setTitle("§l§2Menu");
		$txt = "";
		$form->setContent($txt);
		$form->addButton("§l§cChỉ số của bạn",0,"");		
		$form->addButton("§l§cĐiểm danh hằng ngày",0,"");
		$form->addButton("§l§cVề hub",0,"");
		$form->addButton("§l§cChợ đen",0,"");
		$form->addButton("§l§cWarp",0,"");
		$form->addButton("§l§cSkyBlock Menu",0,"");
		$pure = $this->getServer()->getPluginManager()->getPlugin('PurePerms');
        $userGroup = $pure->getUserDataMgr()->getGroup($player);    					
        if($userGroup == "Vip+" or $userGroup == "Vip++" or $this->getServer()->isOp($player->getName())){
			$form->addButton("§l§cBalo",0,"");
		}		
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function menuFormSB(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
	    $form = $api->createSimpleForm(function (Player $player, int $data = null){
		    $result = $data;
		    if($result === null){
			    return true;
			}
		    switch($result){				
                case "0";	
                    Server::getInstance()->dispatchCommand($player, "sb tp");					
			    break;		
                case "1":
				    Server::getInstance()->dispatchCommand($player, "sb visit");
                break;	
                case "2":
				    Server::getInstance()->dispatchCommand($player, "sb setweather");
                break;				
                default: break;					
			}
		});
		$form->setTitle("§l§2Menu");
		$txt = "";
		$form->setContent($txt);
		$form->addButton("§l§cvề đảo",0,"");		
		$form->addButton("§l§cTham quan đảo người khác",0,"");
		$pure = $this->getServer()->getPluginManager()->getPlugin('PurePerms');
        $userGroup = $pure->getUserDataMgr()->getGroup($player); 
		if($userGroup == "Vip++" or $this->getServer()->isOp($player->getName())){
			$form->addButton("§l§cThay đổi mùa đảo của bạn",0,"textures/ui/Weather");
		}
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function formPremiumCase(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if ($result === null) {
				return true;
			}
			switch($result){				
				case "0";	                  
					$api = $this->getServer()->getPluginManager()->getPlugin('Gems');
					$gems = $api->myMoney($player->getName());
					if($gems >= 10){
						$api->reduceMoney($player, 10);
						$item = $this->getDataItem(130, 0, 1);
						$item->setCustomName(TextFormat::YELLOW.TextFormat::BLUE.TextFormat::RED.TextFormat::GOLD."Premium Case");
						$player->getInventory()->addItem($item);
						$player->sendMessage("§l§aBạn đã mua thành công x1 Premium Case");	
					}else{
						$player->sendMessage("§l§cBạn không đủ Gems để thực hiện giao dịch!");					
					}
				break;
				case "1";	                  
					$api = $this->getServer()->getPluginManager()->getPlugin('Gems');
					$gems = $api->myMoney($player->getName());
					if($gems >= 50){
						$api->reduceMoney($player, 50);
						$item = $this->getDataItem(130, 0, 5);
						$item->setCustomName(TextFormat::YELLOW.TextFormat::BLUE.TextFormat::RED.TextFormat::GOLD."Premium Case");
						$player->getInventory()->addItem($item);
						$player->sendMessage("§l§aBạn đã mua thành công x5 Premium Case");		
					}else{
						$player->sendMessage("§l§cBạn không đủ Gems để thực hiện giao dịch!");					
					}
				break;
				case "2";	                  
					$api = $this->getServer()->getPluginManager()->getPlugin('Gems');
					$gems = $api->myMoney($player->getName());
					if($gems >= 100){
						$api->reduceMoney($player, 100);
						$item = $this->getDataItem(130, 0, 10);
						$item->setCustomName(TextFormat::YELLOW.TextFormat::BLUE.TextFormat::RED.TextFormat::GOLD."Premium Case");
						$player->getInventory()->addItem($item);
						$player->sendMessage("§l§aBạn đã mua thành công x10 Premium Case");	
					}else{
						$player->sendMessage("§l§cBạn không đủ Gems để thực hiện giao dịch!");					
					}
				break;
				case "3";						
				break;				
                default:
                break;					
			}
		});
		$form->setTitle("§l§6Premium Case");
		$txt = 
			"§l§f• §cPremium Case sẽ bao gồm phần quà như sau: \n\n".
			"§l§f• §ePhần thưởng: \n\n".
			"§l§f   ➻ §5Special Iron Ingot §fTỉ lệ: §c25°/ₒ\n\n".
			"§l§f   ➻ §6Gold's God §fTỉ lệ: §c25°/ₒ\n\n".
			"§l§f   ➻ §5Zeus's Handle §fTỉ lệ: §c10°/ₒ\n\n".
			"§l§f   ➻ §dDestoy Sword §fTỉ lệ: §c0.01°/ₒ\n\n".
			"§l§f   ➻ §aCoins random §fTỉ lệ: §c70°/ₒ\n\n"
		;
		$form->setContent($txt);
		$form->addButton("§l§aMua §7x1 §210 gems",0,"textures/ui/realms_slot_check");
		$form->addButton("§l§aMua §7x5 §250 gems",0,"textures/ui/realms_slot_check");
		$form->addButton("§l§aMua §7x10 §2100 gems",0,"textures/ui/realms_slot_check");
		$form->addButton("§lThoát",0,"textures/ui/Ping_Offline_Red_Dark");
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function formTool(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if ($result === null) {
				return true;
			}
			switch($result){				
				case "0";	                  
					$api = $this->getServer()->getPluginManager()->getPlugin('Gems');
					$gems = $api->myMoney($player->getName());
					if($gems >= 5){
						$api->reduceMoney($player, 5);
						$item = $this->getDataItem(369, 0, 1);
						$item->setCustomName(TextFormat::BOLD.TextFormat::GOLD."Gậy phép của thợ xây dựng");
						$nbt = $item->getNamedTag();
		    			$nbt->setString(self::TAG_BUILDER_WAND, "real");
		    			$item->setNamedTag($nbt);
						$item->setLore([
			    			"",
			    			"§6Kĩ năng: Tạo Block §l§eRIGHT CLICK",
							"§7Tạo ra số block mà bạn đã chọn ở điểm 1 và 2,",
							"§7số block phụ thuộc vào túi đồ bạn có bao nhiêu block đó.",
							"",
							"§l§6LEGENDARY",
						]);
						$player->getInventory()->addItem($item);
						$player->sendMessage("§l§aBạn đã mua thành công x1 §l§6Gậy phép của thợ xây dựng");	
					}else{
						$player->sendMessage("§l§cBạn không đủ Gems để thực hiện giao dịch!");					
					}
				break;
				case "1";						
				break;				
                default:
                break;					
			}
		});
		$form->setTitle("§l§aMua dụng cụ");
		$form->addButton("§l§6Gậy phép của thợ xây dựng §7x1 §25 gems",0,"textures/ui/realms_slot_check");
		$form->addButton("§lThoát",0,"textures/ui/Ping_Offline_Red_Dark");
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function formVip(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if ($result === null) {
				return true;
			}
			switch($result){				
				case "0";	                  
					$api = $this->getServer()->getPluginManager()->getPlugin('Gems');
					$gems = $api->myMoney($player->getName());
					if($gems >= 20){
						$pure = $this->getServer()->getPluginManager()->getPlugin('PurePerms');
                        $userGroup = $pure->getUserDataMgr()->getGroup($player);    
                        $group = $pure->getGroup("Vip");						
                        if($userGroup != "Vip"){
							$api->reduceMoney($player, 20);
							$pure->getUserDataMgr()->setGroup($player, $group, null);
							$player->sendMessage("§l§bĐã xử lý giao dịch thành công!");
						}
                        else{
                            $player->sendMessage("Bạn đã có Vip rồi!");
						}       					
					}else{
						$player->sendMessage("§l§cBạn không đủ Gems để thực hiện giao dịch!");					
					}
				break;
				case "1";						
				break;				
                default:
                break;					
			}
		});
		$form->setTitle("§l§aMua Vip");
		$txt = 
			"§l§f• §aVip sẽ được quyền lợi như sau: \n\n".
			"§l§f• §cQuyền lợi: \n\n".
			"§l§f   ➻ §bThêm tính năng +coins khi đánh quái\n\n".
			"§l§f   ➻ §bThêm tính năng +xplevel khi đánh quái\n\n".
			"§l§f   ➻ §c+5 §bđiểm may mắn cho bạn, để loot đồ,..\n\n".
            "§l§f   ➻ §bMở rộng đảo của bạn ra thành §c200x200\n\n".
            "§l§f   ➻ §bĐiểm danh sẽ nhận được 20.000 Coins mỗi ngày\n\n"		
		;
		$form->setContent($txt);
		$form->addButton("§l§aMua §220 gems",0,"textures/ui/realms_slot_check");
		$form->addButton("§lThoát",0,"textures/ui/Ping_Offline_Red_Dark");
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function formVipAdd(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if ($result === null) {
				return true;
			}
			switch($result){				
				case "0";	                  
					$api = $this->getServer()->getPluginManager()->getPlugin('Gems');
					$gems = $api->myMoney($player->getName());
					if($gems >= 30){
						$pure = $this->getServer()->getPluginManager()->getPlugin('PurePerms');
                        $userGroup = $pure->getUserDataMgr()->getGroup($player);    
                        $group = $pure->getGroup("Vip+");						
                        if($userGroup != "Vip+"){
							$api->reduceMoney($player, 30);
							$item = $this->getDataItem(130, 0, 10);
							$item->setCustomName(TextFormat::YELLOW.TextFormat::BLUE.TextFormat::RED.TextFormat::GOLD."Premium Case");
							$player->getInventory()->addItem($item);
							$player->sendMessage("§l§aBạn đã mua thành công x10 Premium Case");
                        	$item = MyItem::getInstance()->getItem("JACOB_HOE", 1);
                        	$player->getInventory()->addItem($item);
							$pure->getUserDataMgr()->setGroup($player, $group, null);
							$player->sendMessage("§l§bĐã xử lý giao dịch thành công!");
						}
                        else{
                            $player->sendMessage("Bạn đã có Vip+ rồi!");
						}       					
					}else{
						$player->sendMessage("§l§cBạn không đủ Gems để thực hiện giao dịch!");					
					}
				break;
				case "1";						
				break;				
                default:
                break;					
			}
		});
		$form->setTitle("§l§aNâng cấp rank Vip+");
		$txt = 
			"§l§f• §aVip+ sẽ được quyền lợi như sau: \n\n".
			"§l§f• §cQuyền lợi: \n\n".
			"§l§f   ➻ §bTất cả quyền của rank Vip\n\n".
			"§l§f   ➻ §bNhận x10 free Premium Case\n\n".
			"§l§f   ➻ §bNhận free ngay x1 Jacob Hoe huyền thoại\n\n".
			"§l§f   ➻ §bThêm tính năng §cBalo bỏ đồ vô tận\n\n".
			"§l§f   ➻ §c+10 §bđiểm may mắn cho bạn, để loot đồ,..\n\n".
            "§l§f   ➻ §bMở rộng đảo của bạn ra thành §c240x240\n\n".
            "§l§f   ➻ §bĐiểm danh sẽ nhận được 30.000 Coins mỗi ngày\n\n"		
		;
		$form->setContent($txt);
		$form->addButton("§l§aMua §230 gems",0,"textures/ui/realms_slot_check");
		$form->addButton("§lThoát",0,"textures/ui/Ping_Offline_Red_Dark");
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function formVipAddAdd(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if ($result === null) {
				return true;
			}
			switch($result){				
				case "0";	                  
					$api = $this->getServer()->getPluginManager()->getPlugin('Gems');
					$gems = $api->myMoney($player->getName());
					if($gems >= 20){
						$pure = $this->getServer()->getPluginManager()->getPlugin('PurePerms');
                        $userGroup = $pure->getUserDataMgr()->getGroup($player);    
                        $group = $pure->getGroup("Vip++");						
                        if($userGroup != "Vip++"){
							$name = $player->getName();
							$api->reduceMoney($player, 20);
							$vip = $this->getConfig()->getNested("database.$name.vip");
                            if(!isset($vip)){
                                $this->getConfig()->setNested("database.$name.vip", microtime(true));
                            }else{
			                    $this->getConfig()->setNested("database.$name.vip", microtime(true));
		                    }
                            $this->getConfig()->save();
							$pure->getUserDataMgr()->setGroup($player, $group, null);
							$player->sendMessage("§l§bĐã xử lý giao dịch thành công!");
						}
                        else{
                            $player->sendMessage("Bạn đã có Vip++ rồi!");
						}       					
					}else{
						$player->sendMessage("§l§cBạn không đủ Gems để thực hiện giao dịch!");					
					}
				break;
				case "1";						
				break;				
                default:
                break;					
			}
		});
		$form->setTitle("§l§aNâng cấp rank Vip++");
		$txt = 
			"§l§f• §aVip++ sẽ được quyền lợi như sau: \n\n".
			"§l§f• §cQuyền lợi: \n\n".
			"§l§f   ➻ §bTất cả quyền của rank Vip\n\n".
			"§l§f   ➻ §bTất cả quyền của rank Vip+\n\n".
			"§l§f   ➻ §bThay đổi thời tiết trong đảo\n\n".
			"§l§f   ➻ §bĐiểm danh sẽ nhận được §640.000 Coins§b mỗi ngày\n\n".
			"§l§f   ➻ §bGiảm tiền Enchant §c50°/ₒ\n\n".
			"§l§f   ➻ §bTăng tiền bán đồ trong §e/sell all §c50°/ₒ\n\n".
			"§l§f   ➻ §bTăng tỉ lệ Enchant lên §c10°/ₒ\n\n".
			"§l§f   ➻ §c+15 §bđiểm may mắn cho bạn, để loot đồ,..\n\n".
            "§l§f   ➻ §bCó thể bay được trong đảo!\n\n".
            "§l§f   ➻ §bCó thể dùng lệnh §c/tpa!\n\n"				
		;
		$form->setContent($txt);
		$form->addButton("§l§aMua §220 gems§7/§c30 ngày",0,"textures/ui/realms_slot_check");
		$form->addButton("§lThoát",0,"textures/ui/Ping_Offline_Red_Dark");
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function formMua(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if ($result === null) {
				return true;
			}
			switch($result){				
				case "0";	                  
					$this->setBiome(1, $player);
					$player->sendMessage(TextFormat::BOLD.TextFormat::AQUA."Đã thay đổi thời tiết thành công!");
				break;
				case "1";	
                    $this->setBiome(35, $player);	
                    $player->sendMessage(TextFormat::BOLD.TextFormat::AQUA."Đã thay đổi thời tiết thành công!");					
				break;	
                case "2";	
                    $this->setBiome(21, $player);
                    $player->sendMessage(TextFormat::BOLD.TextFormat::AQUA."Đã thay đổi thời tiết thành công!");					
				break;
                case "3";	                   
                    $this->setBiome(12, $player);
                    $player->sendMessage(TextFormat::BOLD.TextFormat::AQUA."Đã thay đổi thời tiết thành công!");					
				break;			
                default:
                break;					
			}
		});
		$form->setTitle("§l§aMùa Cho Đảo");
		$txt = "§l§f• §aMùa mà bạn thích cho đảo của bạn là gì?";
		$form->setContent($txt);
		$form->addButton("§cMùa xuân",0,"textures/ui/realms_slot_check");
		$form->addButton("§cMùa hạ (hè)",0,"textures/ui/realms_slot_check");
		$form->addButton("§cMùa thu",0,"textures/ui/realms_slot_check");
		$form->addButton("§cMùa đông",0,"textures/ui/realms_slot_check");
		$form->addButton("§lThoát",0,"textures/ui/Ping_Offline_Red_Dark");
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function setBiome(int $id, Player $player){
		$skyblock = SkyBlocksPM::getInstance()->getPlayerManager()->getPlayer($player)->getSkyblock();
        if ($skyblock == ''){
            $player->sendMessage(TextFormat::RED."Bạn chưa có đảo để thay đổi mùa trong đảo!");
            return;
        }
        $world = $this->getServer()->getWorldManager()->getWorldByName(SkyBlocksPM::getInstance()->getSkyBlockManager()->getSkyBlockByUuid($skyblock)->getWorld());	
		$xa = abs((int)$world->getSpawnLocation()->x + 240);
        $za = abs((int)$world->getSpawnLocation()->z + 240);
        $xb = ($world->getSpawnLocation()->x - 240);
        $zb = ($world->getSpawnLocation()->z - 240);				
		$startTime = microtime(true);
		Math::calculateMinAndMaxValues(new Vector3($xa, 250, $za), new Vector3($xb, 1, $zb), false, $minX, $maxX, $_, $_, $minZ, $maxZ);
		$fillSession = new FillSession($world, false, false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		for($x = $minX; $x <= $maxX; ++$x){
			for($z = $minZ; $z <= $maxZ; ++$z){
				$fillSession->setBiomeAt($x, $z, $id);
			}
		}	
		$result = EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
		$fillSession->reloadChunks($world);
	}
	
	public function onInteractEvent(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$this->blockBreak[$player->getXuid()] = [
		    "X" => (int) $block->getPosition()->x,
			"Y" => (int) $block->getPosition()->y,
			"Z" => (int) $block->getPosition()->z
		];
		$worldBan = [
		    $this->getServer()->getWorldManager()->getDefaultWorld()->getDisplayName(),
			"kitpvp3latinplaynet"
		];
		if(in_array($player->getWorld()->getDisplayName(), $worldBan)){
            if(!in_array($block->getId(), [247, 58, 116])){
			    $event->cancel();
			}
			if($this->getServer()->isOp($player->getName())){
				$event->uncancel();
			}
		}
		/*if($player->getInventory()->getItemInHand()->getId() == 333){
			$pos = $block->getSide(1)->getPosition()->add(0.5, 0.5, 0.5);
			$class = Boat::class;
			$entity = new $class(Location::fromObject($pos, $player->getWorld()));
            $entity->spawnToAll();		
			$count = $player->getInventory()->getItemInHand()->getCount();
			$player->getInventory()->setItemInHand($player->getInventory()->getItemInHand()->setCount($count - 1));
		}*/
	}
	
	public function onPlayerItemUse(PlayerItemUseEvent $event){
		$player = $event->getPlayer();
		$item = $player->getInventory()->getItemInHand();
		if(isset($this->banPlayer[$player->getXuid()])){
			$event->cancel();
		}
		if($item->getCustomName() == TextFormat::RED.TextFormat::BOLD.TextFormat::BLUE."Menu"){
		    $this->menuForm($player);
		}
		if($item->getCustomName() == TextFormat::YELLOW.TextFormat::BLUE.TextFormat::RED.TextFormat::GOLD."Quả Thiên Nhiên"){
			$event->cancel();
			$instance = new EffectInstance(StringToEffectParser::getInstance()->parse("saturation"), 360000, 1);
		    $player->getEffects()->add($instance);
			$count = $player->getInventory()->getItemInHand()->getCount();
			$player->getInventory()->setItemInHand($player->getInventory()->getItemInHand()->setCount($count - 1));		    
		}
	}
	
	public function onInventoryTransaction(InventoryTransactionEvent $event){
		foreach ($event->getTransaction()->getActions() as $action){
            foreach($event->getTransaction()->getInventories() as $inventory){
                $item = $action->getTargetItem();
			    if($item->getCustomName() == TextFormat::RED.TextFormat::BOLD.TextFormat::BLUE."Menu"){
					$event->cancel();
				}
			}
		}
	}
	
	public function onPlayerDropItem(PlayerDropItemEvent $event){
		$player = $event->getPlayer();
		$item = $player->getInventory()->getItemInHand();
		if($item->getCustomName() == TextFormat::RED.TextFormat::BOLD.TextFormat::BLUE."Menu"){
		    $event->cancel();
		}
	}
	
	public function onBreakBlock(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();			
		if(isset($this->blockBreak[$player->getXuid()])){
			$x = $this->blockBreak[$player->getXuid()]["X"];
			$y = $this->blockBreak[$player->getXuid()]["Y"];
			$z = $this->blockBreak[$player->getXuid()]["Z"];
			if((int) $block->getPosition()->x != $x and (int) $block->getPosition()->y != $y and (int) $block->getPosition()->z != $z){
			    if(!$this->getServer()->isOp($player->getName())){
				    $event->cancel();
				}
			}
		}		
		if($player->getWorld()->getDisplayName() == $this->getServer()->getWorldManager()->getDefaultWorld()->getDisplayName()){
		    if(!$this->getServer()->isOp($player->getName())){
				$event->cancel();
			}
		}
		$x = (int)$block->getPosition()->x;
	    $y = (int)$block->getPosition()->y;
	    $z = (int)$block->getPosition()->z;
		if($player->getWorld()->getDisplayName() != $this->getServer()->getWorldManager()->getDefaultWorld()->getDisplayName()){	
			if(!isset($this->breakBlock[$player->getName()])){
				PlayerStats::getInstance()->checkLevel($player, rand(1, 10));
				$this->breakBlock[$player->getName()] = [];
				$this->breakBlock[$player->getName()][$x.$y.$z] = [$block->getPosition()];
			}else{
				if(!isset($this->breakBlock[$player->getName()][$x.$y.$z]) and !isset($this->placeBlock[$player->getName()][$x.$y.$z])){					
					PlayerStats::getInstance()->checkLevel($player, rand(1, 50));
					$this->breakBlock[$player->getName()][$x.$y.$z] = $block->getPosition();
					if(count($this->breakBlock[$player->getName()]) > 4){
						unset($this->breakBlock[$player->getName()]);
					}
				}
			}       
		}
		if(isset($this->inSetUp[$player->getName()])){
			$x = $block->getPosition()->x;
			$y = $block->getPosition()->y;
			$z = $block->getPosition()->z;
			$player->sendMessage(TextFormat::GREEN."Block is x:".$x." y:".$y." z:".$z);
			unset($this->inSetUp[$player->getName()]);
		}
	}
	
	public function onPlaceBlock(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();   
		if($player->getWorld()->getDisplayName() == $this->getServer()->getWorldManager()->getDefaultWorld()->getDisplayName()){
		    if(!$this->getServer()->isOp($player->getName())){
				$event->cancel();
			}
		}
		$hand = $player->getInventory()->getItemInHand();					
		$x = (int)$block->getPosition()->x;
	    $y = (int)$block->getPosition()->y;
	    $z = (int)$block->getPosition()->z;			
        if(!$event->isCancelled()){		
			if(!isset($this->placeBlock[$player->getName()])){
				PlayerStats::getInstance()->checkLevel($player, rand(1, 50));
				$this->placeBlock[$player->getName()] = [];
				$this->placeBlock[$player->getName()][$x.$y.$z] = $block->getPosition();
			}else{
				if(!isset($this->placeBlock[$player->getName()][$x.$y.$z])){					
					PlayerStats::getInstance()->checkLevel($player, rand(1, 50));
					$this->placeBlock[$player->getName()][$x.$y.$z] = $block->getPosition();
				    if(count($this->placeBlock[$player->getName()]) > 10){
						unset($this->placeBlock[$player->getName()]);
					}
				}
			}       
		}	
		if($hand->getCustomName() == TextFormat::YELLOW.TextFormat::BLUE.TextFormat::RED.TextFormat::GOLD."Premium Case"){
			if(rand(1, 100) <= 25){ // 25%
			    $rand = rand(1, 5);
				$item = MyItem::getInstance()->getItem("SPECIAL_IRON_INGOT", $rand);
				$player->sendMessage(TextFormat::GREEN."Bạn đã trúng x$rand ".$item->getCustomName());
				$player->getInventory()->addItem($item);
			}elseif(rand(1, 100) <= 25){ // 25%
			    $rand = rand(1, 5);
				$item = MyItem::getInstance()->getItem("GOLD'S_GOD", $rand);
				$player->sendMessage(TextFormat::GREEN."Bạn đã trúng x$rand ".$item->getCustomName());
				$player->getInventory()->addItem($item);
			}elseif(rand(1, 100) <= 10){ // 10%
				$item = MyItem::getInstance()->getItem("ZEUS'S_HANDLE", 1);
				$this->lightning($player);
				$player->sendMessage(TextFormat::GREEN."Bạn đã trúng x1 ".$item->getCustomName());
				foreach($this->getServer()->getOnlinePlayers() as $online){
					$online->sendMessage(TextFormat::BOLD.TextFormat::YELLOW.">>>> Người chơi ".TextFormat::RED.$player->getName().TextFormat::YELLOW." đã trúng x1 ".$item->getCustomName().TextFormat::YELLOW." <<<<");
				}
				$player->getInventory()->addItem($item);
			}elseif(rand(1, 1000) <= 10){ // 0.01%
				$item = MyItem::getInstance()->getItem("DESTROY_SWORD", 1);
				$this->lightning($player);
				$player->sendMessage(TextFormat::GREEN."Bạn đã trúng x1 ".$item->getCustomName());
				foreach($this->getServer()->getOnlinePlayers() as $online){
					$online->sendMessage(TextFormat::BOLD.TextFormat::GOLD."<<<<0>>>> Người chơi ".TextFormat::RED.$player->getName().TextFormat::GOLD." đã trúng x1 ".$item->getCustomName().TextFormat::GOLD." <<<<0>>>>");
				}
				$player->getInventory()->addItem($item);
			}else{
				$coins = rand(10000, 100000);
				$api = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
				$api->addMoney($player, $coins);
				$player->sendMessage(TextFormat::GREEN."Bạn đã trúng ".TextFormat::GOLD.$coins." Coins");
			}
			$count = $player->getInventory()->getItemInHand()->getCount();
			$player->getInventory()->setItemInHand($player->getInventory()->getItemInHand()->setCount($count - 1));
            $event->cancel();
		}
	}
	
	public function onDamage(EntityDamageEvent $event){
		$entity = $event->getEntity();
		$cause = $event->getCause();
		if($event->getFinalDamage() >= $entity->getHealth()) {
            if($entity instanceof Player){
                if(!Dungeon::getInstance()->inGame($entity)){
				    $event->cancel();		
				    $entity->sendMessage(TextFormat::RED."Bạn đã chết và được hồi sinh tại hub!");				
				    $entity->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
			        $entity->setHealth($entity->getMaxHealth());	
				    $entity->getWorld()->addSound($entity->getLocation()->asVector3(), new AnvilFallSound(), [$entity]);
				}
			}
		}		      
	}
	
	public function onEntityDamageByEntityEvent(EntityDamageByEntityEvent $event){
		$entity = $event->getEntity();	        
		$damager = $event->getDamager();
		if(($entity instanceof Zombie ||
			$entity instanceof Skeleton||
			$entity instanceof IronGolem ||
			$entity instanceof Creeper ||
			$entity instanceof Spider ||
			$entity instanceof God ||
			$entity instanceof SpecialEnderman ||
			$entity instanceof SpecialGolem ||
			$entity instanceof DungeonDrowned ||
			$entity instanceof DungeonEnderman ||
			$entity instanceof DungeonIronZombie ||
			$entity instanceof DungeonZombie ||
			$entity instanceof DungeonSkeleton)
			and $damager instanceof Player
		){
			$pure = $this->getServer()->getPluginManager()->getPlugin('PurePerms');
            $userGroup = $pure->getUserDataMgr()->getGroup($damager);    					
            if($userGroup == "Vip" or $userGroup == "Vip+" or $userGroup == "Vip++"){
			    $this->addCoins[$entity->getId()] = [$damager, microtime(true)];
			}
		}
		if($damager instanceof Player and $entity instanceof Player){
			if($damager->getWorld()->getDisplayName() == $this->getServer()->getWorldManager()->getDefaultWorld()->getDisplayName()){
				$event->cancel();
			}
		}
		//Anti KillAura 
		if($damager instanceof Player){
			if(!isset($this->inCombat[$damager->getXuid()])){
				$this->inCombat[$damager->getXuid()] = [
					"Time" => microtime(true),
 					"Victim" => $entity
				];
			}
			$time = $this->inCombat[$damager->getXuid()]["Time"];
			$diff = microtime(true) - $time;
			if($diff >= 1){
				unset($this->inCombat[$damager->getXuid()]);
				return;
			}
			$victim = $this->inCombat[$damager->getXuid()]["Victim"];
			if($entity->getId() != $victim->getId()){
				$event->cancel();
			}
		}				
	}
	
	public function onDeath(EntityDeathEvent $event){
		$entity = $event->getEntity();			
		//Check in inCombat array 
		foreach($this->inCombat as $xuid => $data){
			$victim = $data["Victim"];
			if($entity->getId() == $victim->getId()){
				unset($this->inCombat[$xuid]);
			}
		}
		
		if(isset($this->addCoins[$entity->getId()])){
			$damager = $this->addCoins[$entity->getId()][0];
			$time = $this->addCoins[$entity->getId()][1];
			if((microtime(true) - $time) <= 5){
			    $api = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');	
			    $api->addMoney($damager, rand(1, 15));
				PlayerStats::getInstance()->checkLevel($damager, rand(1, 50));
			}
			unset($this->addCoins[$entity->getId()]);
		}
		
		if((
		    $entity instanceof IronGolem ||
		    $entity instanceof Creeper ||
		    $entity instanceof Skeleton ||
		    $entity instanceof Zombie)
		    and rand(1, 200) <= 20
	    ){
			$radX = mt_rand(3, 5);
            $radZ = mt_rand(3, 5);
            $pos = $entity->getLocation()->floor();
            $pos->y = $entity->getWorld()->getHighestBlockAt($pos->x += mt_rand(0, 1) ? $radX : -$radX, $pos->z += mt_rand(0, 1) ? $radZ : -$radZ) + 1;

            $entityClasses = [SpecialEnderman::class, SpecialGolem::class];
			$new = new $entityClasses[rand(0, 1)](Location::fromObject($pos, $entity->getWorld()));
            $new->spawnToAll();
			
					
		}
		
		if($entity instanceof SpecialGolem and rand(1, 200) <= 50){
			$radX = mt_rand(3, 24);
            $radZ = mt_rand(3, 24);
            $pos = $entity->getLocation()->floor();
            $pos->y = $entity->getWorld()->getHighestBlockAt($pos->x += mt_rand(0, 1) ? $radX : -$radX, $pos->z += mt_rand(0, 1) ? $radZ : -$radZ) + 1;

            $entityClasses = [God::class, SpecialEnderman::class, SpecialGolem::class];
			if(rand(1, 100) <= 10){
			    $new = new $entityClasses[0](Location::fromObject($pos, $entity->getWorld()));
                $new->spawnToAll();
				
			}
		}
	}
	
	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();		
		if(!isset($this->hasJoined[$player->getXuid()])){
			$this->hasJoined[$player->getXuid()] = $player;
		}
		$this->mainUpdate($player);
		$item = $this->getDataItem(345, 0, 1)->setCustomName(TextFormat::RED.TextFormat::BOLD.TextFormat::BLUE."Menu");
	    $player->getInventory()->setItem(8, $item);
	}
	
	public function onEntityTrampleFarmland(EntityTrampleFarmlandEvent $event){
		$event->cancel();
	}
	
	public function onLeave(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if(isset($this->hasJoined[$player->getXuid()])){
			unset($this->hasJoined[$player->getXuid()]);
		}
	}
	
	public function onMovement(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		$world = $player->getWorld();
        $x = $player->getPosition()->getX();
        $y = $player->getPosition()->getY();
        $z = $player->getPosition()->getZ();
        $block = $player->getWorld()->getBlock(new Vector3($x, $y - 0.5, $z));
        if(isset($this->banPlayer[$player->getXuid()])){
			$event->cancel();
			$player->sendMessage("Hãy đổi tên không có khoảng trống!");
		}
		if($player->getWorld()->getDisplayName() == $this->getServer()->getWorldManager()->getDefaultWorld()->getDisplayName()){
			if($block->getId() === 119){
				Server::getInstance()->dispatchCommand($player, "sb create ".$player->getName());
				Server::getInstance()->dispatchCommand($player, "sb tp");
			}
		}
	}
	
	public function getDataItem(int $id, int $meta = 0, int $count = 1, $tags = null) : Item{
		$class = new ItemFactory();
		return $class->get($id, $meta, $count, $tags);
	}
	
	public function lightning(Player $player) :void{
		$pos = $player->getPosition();		
        $light = new AddActorPacket();
		$light->type = "minecraft:lightning_bolt";
		$light->actorRuntimeId = 1;
		$light->actorUniqueId = 1;
		$light->metadata = [];
		$light->motion = null;
		$light->yaw = $player->getLocation()->getYaw();
		$light->pitch = $player->getLocation()->getPitch();
		$light->position = new Vector3($pos->getX(), $pos->getY(), $pos->getZ());
		$block = $player->getWorld()->getBlock($player->getPosition()->floor()->down());
		$particle = new BlockBreakParticle($block);
        $player->getWorld()->addParticle($pos->asVector3(), $particle, $player->getWorld()->getPlayers());
		$sound = new PlaySoundPacket();
		$sound->soundName = "ambient.weather.thunder";
        $sound->x = $pos->getX();
        $sound->y = $pos->getY();
		$sound->z = $pos->getZ();
		$sound->volume = 1;
		$sound->pitch = 1;
		Server::getInstance()->broadcastPackets($player->getWorld()->getPlayers(), [$light, $sound]);
	}
}