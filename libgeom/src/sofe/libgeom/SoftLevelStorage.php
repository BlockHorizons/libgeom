<?php

/*
 *
 * libgeom
 *
 * Copyright (C) 2017 SOFe
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

declare(strict_types=1);

namespace sofe\libgeom;

use pocketmine\level\Level;
use pocketmine\Server;

abstract class SoftLevelStorage{
	/** @var string|null */
	protected $levelName = null;

	public function getLevel(Server $server){
		if($this->levelName === null){
			return null;
		}
		return $server->getLevelByName($this->levelName);
	}

	public function getLevelName() : string{
		return $this->levelName;
	}

	public function setLevel(Level $level){
		if($level !== null and $level->isClosed()){
			throw new \InvalidArgumentException("Cannot use unloaded level");
		}
		$this->levelName = $level->getFolderName();
		return $this;
	}
}
