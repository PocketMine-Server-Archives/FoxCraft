<?php

namespace hachkingtohach1\PlayerStats\provider\sql;

use mysqli;
use pocketmine\player\Player;
use hachkingtohach1\PlayerStats\PlayerStats;
use hachkingtohach1\PlayerStats\provider\DataBase;
use hachkingtohach1\PlayerStats\utils\SQLUtils;

class SQL implements DataBase{

    private $db;
    private $plugin;
    public $dbName;
   
    public function __construct(string $dbName){ $this->plugin = PlayerStats::getInstance();
        $this->dbName = $dbName;
        $config = $this->plugin->getConfig()->getNested("MySQL-Info");

        $this->db = new mysqli(
			$config["Host"] ?? "127.0.0.1",
			$config["User"] ?? "root",
			$config["Password"] ?? "",
			$config["Database"] ?? "1",
			$config["Port"] ?? 3306
		);
			
		if($this->db->connect_error){
			$this->plugin->getLogger()->critical("Could not connect to MySQL server: ".$this->db->connect_error);
			return;
		}
		
		if(!$this->db->query("CREATE TABLE if NOT EXISTS user_profile(
			    username VARCHAR(20) PRIMARY KEY,
			    health FLOAT,
				defense FLOAT,
				truedefense FLOAT,
				strength FLOAT,
				speed FLOAT,
				critchance FLOAT,
				critdamage FLOAT,
				intelligence FLOAT,
				miningspeed FLOAT,
				attackspeed FLOAT,
				miningfortune FLOAT,
				farmingfortune FLOAT,
				foragingfortune FLOAT,
				ferocity FLOAT,
				levelnow FLOAT,
				xplevel FLOAT
		    );"
		)){
		    $this->plugin->getLogger()->critical("Error creating table: " . $this->db->error);
		    return;
		}		
    } 
	
	public function getDatabaseName(): string{
        return $this->dbName;
    }

    public function getData(): SQLUtils{}
   
    public function close(): void{}
  
    public function reset(): void{} 	

    public function accountExists($player){
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);

		$result = $this->db->query("SELECT * FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		return $result->num_rows > 0 ? true:false;
	}	
	
	public function createProfile($player) :bool{
		if($player instanceof Player){
			$nameplayer = $player->getXuid();
		}
		$nameplayer = strtolower($nameplayer);
		if(!$this->accountExists($nameplayer)){
			$this->db->query("INSERT INTO user_profile (username, health, defense, truedefense, strength, speed, critchance, critdamage, intelligence, miningspeed, attackspeed, miningfortune, farmingfortune, foragingfortune, ferocity, levelnow, xplevel)
			VALUES ('".$this->db->real_escape_string($nameplayer)."', 100.0, 100.0, 0.0, 10.0, 100.0, 10.0, 100.0, 100.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0);");
			return true;
		}
		return false;
	}
	
	public function removeProfile($player) :bool{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		if($this->db->query("DELETE FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'") === true) return true;
		return false;
	}
	
	public function getLevel($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT levelnow FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function setLevel($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET levelnow = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function getXpLevel($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT xplevel FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function setXpLevel($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET xplevel = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function getHealth($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT health FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getDefense($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT defense FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getTrueDefense($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT truedefense FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getStrength($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT strength FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getSpeed($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT speed FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getCritChance($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT critchance FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getCritDamage($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT critdamage FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getIntelligence($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT intelligence FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getMiningSpeed($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT miningspeed FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getAttackSpeed($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT attackspeed FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getMiningFortune($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT miningfortune FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getFarmingFortune($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT farmingfortune FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getForagingFortune($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT foragingfortune FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function getFerocity($player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT ferocity FROM user_profile WHERE username='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	public function setHealth($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET health = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setDefense($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET defense = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setCritDamage($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET critdamage = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setCritChance($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET critchance = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setStrength($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET strength = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setTrueDefense($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET truedefense = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setSpeed($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET speed = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setIntelligence($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET intelligence = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setMiningSpeed($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET miningspeed = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setAttackSpeed($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET attackspeed = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setMiningFortune($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET miningfortune = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setFarmingFortune($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET farmingfortune = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setForagingFortune($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET foragingfortune = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function setFerocity($player, $amount) :float{
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		$amount = (float) $amount;
		return $this->db->query("UPDATE user_profile SET ferocity = $amount WHERE username='".$this->db->real_escape_string($player)."'");
	}
	
	public function getLevelAll(){
		$res = $this->db->query("SELECT * FROM user_profile");
		$ret = [];
		foreach($res->fetch_all() as $val){
			$ret[$val[0]] = $val[15];
		}
		$res->free();
		return $ret;
	}
}