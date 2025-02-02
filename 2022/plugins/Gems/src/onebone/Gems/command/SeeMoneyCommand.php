<?php

namespace onebone\Gems\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;

use onebone\Gems\Gems;

class SeeMoneyCommand extends Command
{

    public function __construct(private Gems $plugin)
    {
        $desc = $plugin->getCommandMessage("seegems");
        parent::__construct("seegems", $desc["description"], $desc["usage"]);

        $this->setPermission("gems.command.seemoney");

        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $params): bool
    {
        if (!$this->plugin->isEnabled()) return false;
        if (!$this->testPermission($sender)) {
            return false;
        }

        $player = array_shift($params);
        if (trim($player) === "") {
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            return true;
        }

        if (($p = $this->plugin->getServer()->getPlayerByPrefix($player)) instanceof Player) {
            $player = $p->getName();
        }

        $money = $this->plugin->myMoney($player);
        if ($money !== false) {
            $sender->sendMessage($this->plugin->getMessage("seemoney-seemoney", [$player, $money], $sender->getName()));
        } else {
            $sender->sendMessage($this->plugin->getMessage("player-never-connected", [$player], $sender->getName()));
        }
        return true;
    }
}
