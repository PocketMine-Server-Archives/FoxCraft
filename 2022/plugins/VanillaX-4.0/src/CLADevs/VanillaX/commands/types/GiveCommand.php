<?php

namespace CLADevs\VanillaX\commands\types;

use CLADevs\VanillaX\commands\Command;
use CLADevs\VanillaX\commands\utils\CommandArgs;
use CLADevs\VanillaX\commands\utils\CommandOverload;
use CLADevs\VanillaX\commands\utils\CommandTargetSelector;
use CLADevs\VanillaX\VanillaX;
use Exception;
use pocketmine\command\CommandSender;
use pocketmine\item\Durable;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\nbt\JsonNbtParser;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\player\Player;

class GiveCommand extends Command{

    public function __construct(){
        parent::__construct("give", "Gives an item to a player.");
        $this->setPermission("give.command");
        $this->commandArg = new CommandArgs(PlayerPermissions::MEMBER);

        $overload = new CommandOverload();
        $overload->addTarget("player");
        $overload->addEnum("itemName", new CommandEnum("Item", []));
        $overload->addInt("amount");
        $overload->addInt("data");
        $overload->addJson("components");
        $this->commandArg->addOverload($overload);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$this->testPermission($sender)) return;
        if((!isset($args[0]) || !isset($args[1])) && !$sender instanceof Player){
            $this->sendSyntaxError($sender, "", "/$commandLabel");
            return;
        }
        $player = [$sender];
        $itemName = null;

        if(isset($args[0])){
            if(!$player = CommandTargetSelector::getFromString($sender, $args[0], true, true, true)) return;
            if(isset($args[1])){
                try{
                    $itemName = LegacyStringToItemParser::getInstance()->parse($args[1]);
                }catch (Exception){
                    $this->sendSyntaxError($sender, $args[1], "/$commandLabel", $args[1]);
                    return;
                }

                if(isset($args[2])){
                    if(!is_numeric($args[2])){
                        $this->sendSyntaxError($sender, $args[2], "/$commandLabel", $args[2], [$args[0], $args[1]]);
                        return;
                    }
                    $itemName->setCount(intval($args[2]));

                    if(isset($args[3]) && $itemName instanceof Durable){
                        if(!is_numeric($args[3])){
                            $this->sendSyntaxError($sender, $args[3], "/$commandLabel", $args[3], [$args[0], $args[1], $args[2]]);
                            return;
                        }
                        $itemName->setDamage(intval($args[3]));

                        if(isset($args[4])){
                            $data = implode(" ", array_slice($args, 4));
                            try{
                                $tags = JsonNbtParser::parseJson($data);
                            }catch(Exception $ex){
                                VanillaX::getInstance()->getLogger()->logException($ex);
                                $this->sendSyntaxError($sender, $args[4], "/$commandLabel", $args[4], [$args[0], $args[1], $args[2], $args[3]]);
                                return;
                            }
                            $itemName->setNamedTag($tags);
                        }
                    }
                }
            }
        }
        foreach($player as $p){
            if(!$p instanceof Player){
                continue;
            }
            $sender->sendMessage("Gave " . $itemName->getName() . " * " . $itemName->getCount() . " to " . $p->getName());
            $p->getInventory()->addItem(clone $itemName);
        }
    }
}