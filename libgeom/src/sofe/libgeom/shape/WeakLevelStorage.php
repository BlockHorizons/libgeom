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

namespace sofe\libgeom\shape;

use pocketmine\level\Level;
use pocketmine\utils\MainLogger;

abstract class WeakLevelStorage{
	/** @var Level|null */
	private $level = null;

	public function getLevel(){
		if($this->level !== null and $this->level->isClosed()){
			MainLogger::getLogger()->debug(static::class . " was holding a reference to an unloaded Level");
			$this->level = null;
		}

		return $this->level;
	}

	public function setLevel(Level $level = null){
		if($level !== null and $level->isClosed()){
			throw new \InvalidArgumentException("Specified level has been unloaded and cannot be used");
		}

		$this->level = $level;
		return $this;
	}

	public function isValid() : bool{
		return $this->getLevel() instanceof Level;
	}
}
