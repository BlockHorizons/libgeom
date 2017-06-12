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

namespace sofe\libgeom\shape\lazystreams;

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use sofe\libgeom\shape\BlockStream;
use sofe\libgeom\shape\EndOfBlockStreamException;

class LazyStreamsSolidStream extends BlockStream{
	protected $shape;
	protected $x0, $x1, $y0, $y1, $z0, $z1;

	protected $x, $y, $z;
	protected $tmpVector;

	public function __construct(LazyStreamsShape $shape){
		$this->shape = $shape;
		$this->x0 = $shape->getMinX();
		$this->x1 = $shape->getMaxX();
		$this->y0 = $shape->getMinY();
		$this->y1 = $shape->getMaxY();
		$this->z0 = $shape->getMinZ();
		$this->z1 = $shape->getMaxZ();
		$this->tmpVector = new Vector3;
		$this->rewind();
	}

	public function nextVector(){
		while(true){
			++$this->tmpVector->x;
			if($this->tmpVector->x > $this->x1){
				++$this->tmpVector->y;
				$this->tmpVector->x = $this->x0;
				if($this->tmpVector->y > $this->y1){
					++$this->tmpVector->z;
					$this->tmpVector->y = $this->y0;
					if($this->tmpVector->z > $this->z1){
						throw new EndOfBlockStreamException;
					}
				}
			}
			if($this->validateVector($this->tmpVector)){
				return clone $this->tmpVector;
			}
			return null; // check timing first
		}
		throw new \RuntimeException("Code logic problem");
	}

	protected function rewind(){
		$this->tmpVector->x = $this->x0 - 1;
		$this->tmpVector->y = $this->y0;
		$this->tmpVector->z = $this->z0;
	}

	public function getLevel() : Level{
		return $this->shape->getLevel();
	}

	public function maxSize() : int{
		return (int) ceil($this->shape->getEstimatedSize() * 1.5); // a rough guess
	}

	protected function validateVector(Vector3 $vector) : bool{
		return $this->shape->isInside($vector);
	}
}
