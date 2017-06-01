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

use pocketmine\math\Vector3;

abstract class Shape extends WeakLevelStorage{
	public abstract function isInside(Vector3 $vector) : bool;

	public abstract function estimateSize() : int;

	public abstract function getSolidStream() : BlockStream;

	public abstract function getShallowStream(float $padding, float $margin) : BlockStream;
}
