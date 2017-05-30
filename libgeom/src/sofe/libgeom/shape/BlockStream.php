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

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

abstract class BlockStream{
	/**
	 * Returns the next block to be iterated, or null if no blocks
	 *
	 * @return Block|null
	 */
	public final function nextBlock(){
		$vector = $this->nextVector();
		return $vector !== null ? $this->getLevel()->getBlock($vector) : null;
	}

	/**
	 * Returns the next Position to be iterated, or null if iterator has ended
	 *
	 * @return Position|null
	 */
	public final function nextPosition(){
		$vector = $this->nextVector();
		return $vector !== null ? new Position($vector->x, $vector->y, $vector->z, $this->getLevel()) : null;
	}

	/**
	 * Returns the next Vector3 to be iterated, or null if iterator has ended
	 *
	 * @return Vector3|null
	 */
	public abstract function nextVector();

	protected abstract function rewind();

	public abstract function getLevel() : Level;

	public abstract function maxSize() : int;
}
