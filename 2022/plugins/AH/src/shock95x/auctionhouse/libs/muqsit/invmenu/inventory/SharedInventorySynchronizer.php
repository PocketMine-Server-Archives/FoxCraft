<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\libs\muqsit\invmenu\inventory;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\item\Item;

final class SharedInventorySynchronizer implements InventoryListener{

	protected Inventory $inventory;

	public function __construct(Inventory $inventory){
		$this->inventory = $inventory;
	}

	public function getSynchronizingInventory() : Inventory{
		return $this->inventory;
	}

	public function onContentChange(Inventory $inventory, array $old_contents) : void{
		$this->inventory->setContents($inventory->getContents());
	}

	public function onSlotChange(Inventory $inventory, int $slot, Item $old_item) : void{
		$this->inventory->setItem($slot, $inventory->getItem($slot));
	}
}