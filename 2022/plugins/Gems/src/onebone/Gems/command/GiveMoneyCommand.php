<?php

namespace onebone\Gems\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;

use onebone\Gems\Gems;

class GiveMoneyCommand extends Command{

	public function __construct(private Gems $plugin){
		$desc = $plugin->getCommandMessage("givegems");
		parent::__construct("givegems", $desc["description"], $desc["usage"]);

		$this->setPermission("gems.command.givemoney");

		$this->plugin = $plugin;
	}

	public function execute(CommandSender $sender, string $label, array $params): bool{
		if(!$this->plugin->isEnabled()) return false;
		if(!$this->testPermission($sender)){
			return false;
		}

		$player = array_shift($params);
		$amount = array_shift($params);

		if(!is_numeric($amount)){
			$sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
			return true;
		}

		if(($p = $this->plugin->getServer()->getPlayerByPrefix($player)) instanceof Player){
			$player = $p->getName();
		}

		$result = $this->plugin->addMoney($player, $amount,false,'Gems.command.give');
		switch($result){
			case Gems::RET_INVALID:
			$sender->sendMessage($this->plugin->getMessage("givemoney-invalid-number", [$amount], $sender->getName()));
			break;
			case Gems::RET_SUCCESS:
			$sender->sendMessage($this->plugin->getMessage("givemoney-gave-money", [$amount, $player], $sender->getName()));

			if($p instanceof Player){
				$p->sendMessage($this->plugin->getMessage("givemoney-money-given", [$amount], $sender->getName()));
			}
			break;
			case Gems::RET_CANCELLED:
			$sender->sendMessage($this->plugin->getMessage("request-cancelled", [], $sender->getName()));
			break;
			case Gems::RET_NO_ACCOUNT:
			$sender->sendMessage($this->plugin->getMessage("player-never-connected", [$player], $sender->getName()));
			break;
		}
        return true;
	}
}
