<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\libs\muqsit\invmenu\type;

use shock95x\auctionhouse\libs\muqsit\invmenu\inventory\InvMenuInventory;
use shock95x\auctionhouse\libs\muqsit\invmenu\InvMenu;
use shock95x\auctionhouse\libs\muqsit\invmenu\type\graphic\BlockInvMenuGraphic;
use shock95x\auctionhouse\libs\muqsit\invmenu\type\graphic\InvMenuGraphic;
use shock95x\auctionhouse\libs\muqsit\invmenu\type\graphic\network\InvMenuGraphicNetworkTranslator;
use shock95x\auctionhouse\libs\muqsit\invmenu\type\util\InvMenuTypeHelper;
use pocketmine\block\Block;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;
use pocketmine\world\World;

final class BlockFixedInvMenuType implements FixedInvMenuType{

	private Block $block;
	private int $size;
	private ?InvMenuGraphicNetworkTranslator $network_translator;

	public function __construct(Block $block, int $size, ?InvMenuGraphicNetworkTranslator $network_translator = null){
		$this->block = $block;
		$this->size = $size;
		$this->network_translator = $network_translator;
	}

	public function getSize() : int{
		return $this->size;
	}

	public function createGraphic(InvMenu $menu, Player $player) : ?InvMenuGraphic{
		$origin = $player->getPosition()->addVector(InvMenuTypeHelper::getBehindPositionOffset($player))->floor();
		if($origin->y < World::Y_MIN || $origin->y >= World::Y_MAX){
			return null;
		}

		return new BlockInvMenuGraphic($this->block, $origin, $this->network_translator);
	}

	public function createInventory() : Inventory{
		return new InvMenuInventory($this->size);
	}
}