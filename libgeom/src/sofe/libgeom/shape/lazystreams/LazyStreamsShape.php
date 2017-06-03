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

	private $minX, $minY, $minZ, $maxX, $maxY, $maxZ;

	protected function onDimenChanged(){
		unset($this->minX, $this->minY, $this->minZ, $this->maxX, $this->maxY, $this->maxZ);
	}

	public function getMinX() : int{
		if(!isset($this->minX)){
			$this->minX = (int) floor($this->lazyGetMinX());
		}
		return $this->minX;
	}

	public function getMinY() : int{
		if(!isset($this->minY)){
			$this->minY = (int) floor($this->lazyGetMinY());
		}
		return $this->minY;
	}

	public function getMinZ() : int{
		if(!isset($this->minZ)){
			$this->minZ = (int) floor($this->lazyGetMinZ());
		}
		return $this->minZ;
	}

	public function getMaxX() : int{
		if(!isset($this->maxX)){
			$this->maxX = (int) ceil($this->lazyGetMaxX());
		}
		return $this->maxX;
	}

	public function getMaxY() : int{
		if(!isset($this->maxY)){
			$this->maxY = (int) ceil($this->lazyGetMaxY());
		}
		return $this->maxY;
	}

	public function getMaxZ() : int{
		if(!isset($this->maxZ)){
			$this->maxZ = (int) ceil($this->lazyGetMaxZ());
		}
		return $this->maxZ;
	}

	protected abstract function lazyGetMinX() : float;

	protected abstract function lazyGetMinY() : float;

	protected abstract function lazyGetMinZ() : float;

	protected abstract function lazyGetMaxX() : float;

	protected abstract function lazyGetMaxY() : float;

	protected abstract function lazyGetMaxZ() : float;

	public function getSolidStream() : BlockStream{
		return new LazyStreamsSolidStream($this);
	}

	public function getShallowStream(float $padding, float $margin, float $maxSize) : BlockStream{
		return new LazyStreamsShallowStream($this, $padding, $margin, $maxSize);
	}
}
