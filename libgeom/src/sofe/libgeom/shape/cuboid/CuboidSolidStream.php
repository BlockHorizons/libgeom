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

namespace sofe\libgeom\shape\cuboid;

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use sofe\libgeom\shape\BlockStream;
use sofe\libgeom\shape\EndOfBlockStreamException;

class CuboidSolidStream extends BlockStream{
	/** @var CuboidShape */
	private $shape;

	private $ix, $iy, $iz;
	private $fx, $fy, $fz;

	/** @var Vector3 */
	private $tv;

	public function __construct(CuboidShape $shape){
		$this->shape = $shape;
		$this->ix = $this->shape->getMin()->getFloorX() - 1;
		$this->iy = $this->shape->getMin()->getFloorY();
		$this->iz = $this->shape->getMin()->getFloorZ();
		$this->fx = $this->shape->getMax()->getFloorX();
		$this->fy = $this->shape->getMax()->getFloorY();
		$this->fz = $this->shape->getMax()->getFloorZ();
		assert($this->ix <= $this->fx && $this->iy <= $this->fy && $this->iz <= $this->fz);
		$this->rewind();
	}

	protected function rewind(){
		$this->tv = new Vector3($this->ix - 1, $this->iy, $this->iz);
	}

	public function nextVector(){
		++$this->tv->x;
		if($this->ix > $this->fx){
			$this->tv->x = $this->ix;
			++$this->tv->y;
			if($this->tv->y > $this->fy){
				$this->tv->y = $this->iy;
				++$this->tv->z;
				if($this->tv->z > $this->fz){
					throw new EndOfBlockStreamException;
				}
			}
		}
		return $this->tv;
	}

	public function getLevel() : Level{
		return $this->shape->getLevel();
	}

	public function maxSize() : int{
		return $this->shape->getEstimatedSize();
	}
}
