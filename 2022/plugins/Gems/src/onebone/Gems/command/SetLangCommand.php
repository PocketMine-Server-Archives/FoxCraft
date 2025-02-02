<?php

namespace onebone\Gems\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

use onebone\Gems\Gems;

class SetLangCommand extends Command
{

    public function __construct(private Gems $plugin)
    {
        $desc = $plugin->getCommandMessage("setlag");
        parent::__construct("setlag", $desc["description"], $desc["usage"]);

        $this->setPermission("gems.command.setlang");

        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $params): bool
    {
        if (!$this->plugin->isEnabled()) return false;
        if (!$this->testPermission($sender)) {
            return false;
        }

        $lang = array_shift($params);
        if (trim($lang) === "") {
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            return true;
        }

        if ($this->plugin->setPlayerLanguage($sender->getName(), $lang)) {
            $sender->sendMessage($this->plugin->getMessage("language-set", [$lang], $sender->getName()));
        } else {
            $sender->sendMessage(TextFormat::RED . "There is no language such as $lang");
        }
        return true;
    }
}
