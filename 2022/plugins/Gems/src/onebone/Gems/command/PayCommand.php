<?php

namespace onebone\Gems\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;

use onebone\Gems\Gems;
use onebone\Gems\event\money\PayMoneyEvent;

class PayCommand extends Command{

	public function __construct(private Gems $plugin){
		$desc = $plugin->getCommandMessage("payg");
		parent::__construct("payg", $desc["description"], $desc["usage"]);

		$this->setPermission("gems.command.pay");

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

		$player = array_shift($params);
		$amount = array_shift($params);

		if(!is_numeric($amount)){
			$sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
			return true;
		}

		if(($p = $this->plugin->getServer()->getPlayerByPrefix($player)) instanceof Player){
			$player = $p->getName();
		}

		if(!$p instanceof Player and $this->plugin->getConfig()->get("allow-pay-offline", true) === false){
			$sender->sendMessage($this->plugin->getMessage("player-not-connected", [$player], $sender->getName()));
			return true;
		}

		if(!$this->plugin->accountExists($player)){
			$sender->sendMessage($this->plugin->getMessage("player-never-connected", [$player], $sender->getName()));
			return true;
		}

        $ev = new PayMoneyEvent($this->plugin, $sender->getName(), $player, $amount);
        $ev->call();

		$result = Gems::RET_CANCELLED;
		if(!$ev->isCancelled()){
			$result = $this->plugin->reduceMoney($sender, $amount,false,'Gems.command.pay');
		}

		if($result === Gems::RET_SUCCESS){
			$this->plugin->addMoney($player, $amount, true,'Gems.command.pay');

			$sender->sendMessage($this->plugin->getMessage("pay-success", [$amount, $player], $sender->getName()));
			if($p instanceof Player){
				$p->sendMessage($this->plugin->getMessage("money-paid", [$sender->getName(), $amount], $sender->getName()));
			}
		}else{
			$sender->sendMessage($this->plugin->getMessage("pay-failed", [$player, $amount], $sender->getName()));
		}
		return true;
	}
}
