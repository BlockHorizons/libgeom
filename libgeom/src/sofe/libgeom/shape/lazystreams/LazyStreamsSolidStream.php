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

namespace sofe\libgeom\shape\lazystreams;

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use sofe\libgeom\shape\BlockStream;
use sofe\libgeom\shape\Shape;

class LazyStreamsSolidStream extends BlockStream{
	private $shape;

	public function __construct(Shape $shape){
		$this->shape = $shape;
	}

	/**
	 * Returns the next Vector3 to be iterated, or null if iterator has ended
	 *
	 * @return Vector3|null
	 */
	public function nextVector(){
		// TODO: Implement nextVector() method.
	}

	protected function rewind(){
		// TODO: Implement rewind() method.
	}

	public function getLevel() : Level{
		return $this->shape->getLevel();
	}

	public function maxSize() : int{
		return ceil($this->shape->estimateSize() * 1.5); // a rough guess
	}
}
