<?php

namespace onebone\Gems\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;

use onebone\Gems\Gems;

class MyStatusCommand extends Command{

	public function __construct(private Gems $plugin){
		$desc = $plugin->getCommandMessage("mystatuss");
		parent::__construct("mystatuss", $desc["description"], $desc["usage"]);

		$this->setPermission("gems.command.mystatus");

		$this->plugin = $plugin;
	}

	public function execute(CommandSender $sender, string $label, array $params): bool{
		if(!$this->plugin->isEnabled()) return false;
		if(!$this->testPermission($sender)){
			return false;
		}

		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
			return true;
		}

		$money = $this->plugin->getAllMoney();

		$allMoney = 0;
		foreach($money as $m){
			$allMoney += $m;
		}
		$topMoney = 0;
		if($allMoney > 0){
			$topMoney = round((($money[strtolower($sender->getName())] / $allMoney) * 100), 2);
		}

		$sender->sendMessage($this->plugin->getMessage("mystatus-show", [$topMoney], $sender->getName()));
		return true;
	}
}
