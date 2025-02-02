<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\libs\muqsit\invmenu\inventory;

use pocketmine\block\inventory\BlockInventory;
use pocketmine\inventory\SimpleInventory;
use pocketmine\world\Position;

final class InvMenuInventory extends SimpleInventory implements BlockInventory{

	protected Position $holder;

	public function __construct(int $size){
		parent::__construct($size);
		$this->holder = new Position(0, 0, 0, null);
	}

	public function getHolder() : Position{
		return $this->holder;
	}
}