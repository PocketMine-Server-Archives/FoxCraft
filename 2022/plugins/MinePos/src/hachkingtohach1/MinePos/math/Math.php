<?php

declare(strict_types=1);

namespace hachkingtohach1\MinePos\math;

use pocketmine\{Server,math\Vector3,world\Level,world\Position};

class Math {	
    /**
	 * use to convert string $str to pos for generator x, y, z
     */	
	public static function mathStr(String $str): Position 
	{
        return new Position((float)explode('_', $str)[0], (float)explode('_', $str)[1], (float)explode('_', $str)[2], Server::getInstance()->getWorldManager()->getWorldByName(explode('_', $str)[3]));
	}

    /**
	 * use to convert pos x, y, z to x_y_z_nameworld 
     */
    public static function mathPostion(Position $pos): String 
	{
        return (int)$pos->x .'_'.(int)$pos->y.'_'.(int)$pos->z.'_'.$pos->getWorld()->getDisplayName();
	}
}