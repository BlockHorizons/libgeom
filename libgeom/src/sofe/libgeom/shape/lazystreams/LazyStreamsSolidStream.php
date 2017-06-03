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
use sofe\libgeom\shape\EndOfBlockStreamException;

class LazyStreamsSolidStream extends BlockStream{
	protected $shape;
	private $x0, $x1, $y0, $y1, $z0, $z1;

	private $x, $y, $z;

	public function __construct(LazyStreamsShape $shape){
		$this->shape = $shape;
		$this->x0 = $shape->getMinX();
		$this->x1 = $shape->getMaxX();
		$this->y0 = $shape->getMinY();
		$this->y1 = $shape->getMaxY();
		$this->z0 = $shape->getMinZ();
		$this->z1 = $shape->getMaxZ();
	}

	public function nextVector(){
		static $vector = null;
		if($vector === null){
			$vector = new Vector3;
		}
		while(true){
			++$this->x;
			if($this->x > $this->x1){
				++$this->y;
				$this->x = $this->x0;
				if($this->y > $this->y1){
					++$this->z;
					$this->y = $this->y0;
					if($this->z > $this->z1){
						throw new EndOfBlockStreamException;
					}
				}
			}
			$vector->setComponents($this->x, $this->y, $this->z);
			if($this->validateVector($vector)){
				return clone $vector;
			}
			return null; // check timing first
		}
		throw new \RuntimeException("Code logic problem");
	}

	protected function rewind(){
		$this->x = $this->x0 - 1;
		$this->y = $this->y0;
		$this->z = $this->z0;
	}

	public function getLevel() : Level{
		return $this->shape->getLevel();
	}

	public function maxSize() : int{
		return (int) ceil($this->shape->estimateSize() * 1.5); // a rough guess
	}

	protected function validateVector(Vector3$vector):bool{
		return $this->shape->isInside($vector);
	}
}
