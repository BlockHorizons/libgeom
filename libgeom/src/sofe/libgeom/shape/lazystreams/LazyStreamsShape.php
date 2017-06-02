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

use pocketmine\math\Vector3;
use sofe\libgeom\shape\BlockStream;
use sofe\libgeom\shape\Shape;

abstract class LazyStreamsShape extends Shape{
	public abstract function marginalDistance(Vector3 $vector) : float;

	protected abstract function lazyGetMinX() : int;

	protected abstract function lazyGetMinY() : int;

	protected abstract function lazyGetMinZ() : int;

	protected abstract function lazyGetMaxX() : int;

	protected abstract function lazyGetMaxY() : int;

	protected abstract function lazyGetMaxZ() : int;

	public function getSolidStream() : BlockStream{
		return new LazyStreamsSolidStream($this);
	}

	public function getShallowStream(float $padding, float $margin) : BlockStream{
		// TODO: Implement getShallowStream() method.
	}
}
