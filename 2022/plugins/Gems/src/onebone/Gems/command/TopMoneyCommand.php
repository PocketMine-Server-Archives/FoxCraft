<?php

/*
 * EconomyS, the massive economy plugin with many features for PocketMine-MP
 * Copyright (C) 2013-2017  onebone <jyc00410@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace onebone\Gems\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use onebone\Gems\Gems;
use onebone\Gems\task\SortTask;

class TopMoneyCommand extends Command
{

    public function __construct(private Gems $plugin)
    {
        $desc = $plugin->getCommandMessage("topgems");
        parent::__construct("topgems", $desc["description"], $desc["usage"]);

        $this->setPermission("gems.command.topmoney");

        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $params): bool
    {
        if (!$this->plugin->isEnabled()) return false;
        if (!$this->testPermission($sender)) return false;

        $page = (int)array_shift($params);

        $server = $this->plugin->getServer();

        $banned = [];
        foreach ($server->getNameBans()->getEntries() as $entry) {
            if ($this->plugin->accountExists($entry->getName())) {
                $banned[] = $entry->getName();
            }
        }
        $ops = [];
        foreach ($server->getOps()->getAll() as $op) {
            if ($this->plugin->accountExists($op)) {
                $ops[] = $op;
            }
        }

        $task = new SortTask($sender->getName(), $this->plugin->getAllMoney(), $this->plugin->getConfig()->get("add-op-at-rank"), $page, $ops, $banned);
        $server->getAsyncPool()->submitTask($task);
        return true;
    }
}
