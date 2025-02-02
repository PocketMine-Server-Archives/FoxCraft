<?php
namespace shock95x\auctionhouse\task;

use DateTime;
use pocketmine\scheduler\Task;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\libs\SOFe\AwaitGenerator\Await;

class ListingExpireTask extends Task {

	public function __construct(public Database $database) {}

	public function onRun(): void {
		Await::f2c(function () {
			$time = new DateTime();
			$dataStorage = DataStorage::getInstance();
			$listings = $this->createListingsFromRows(yield $this->database->asyncSelectRaw("SELECT * from auctions WHERE :time >= end_time AND expired = FALSE;", ["time" => time()]));
			foreach ($listings as $listing) {
				yield $dataStorage->setExpired($listing, yield) => Await::ONCE;
				(new AuctionEndEvent($listing, AuctionEndEvent::EXPIRED))->call();
			}
			$listings = $this->createListingsFromRows(yield $this->database->asyncSelectRaw("SELECT * from auctions WHERE expired = TRUE;"));
			foreach ($listings as $listing) {
				$time->setTimestamp($listing->getEndTime());
				if($time->diff(new DateTime())->days >= Settings::getExpiredDuration()) {
					yield $dataStorage->removeListing($listing, yield, yield Await::REJECT) => Await::ONCE;
					(new AuctionEndEvent($listing, AuctionEndEvent::EXPIRED_PURGED))->call();
				}
			}
		});
	}

	private function createListingsFromRows(array $listings): array {
		$array = [];
		foreach ($listings as $rows) {
			$array[] = $this->database->createListingFromRows($rows);
		}
		return $array;
	}
}