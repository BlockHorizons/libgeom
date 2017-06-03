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

namespace sofe\libgeom\shape\cuboid;

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use sofe\libgeom\shape\BlockStream;
use sofe\libgeom\shape\EndOfBlockStreamException;

class CuboidSimpleShallowStream extends BlockStream{
	/** @var CuboidShape */
	private $cuboid;
	/** @var Vector3 */
	private $min, $max;
	/** @var int */
	private $phase;
	/** @var int */
	private $a, $b, $minA, $maxA, $maxB, $fixed;

	public function __construct(CuboidShape $cuboid){
		$this->cuboid = $cuboid;
		$this->min = $cuboid->getMin();
		$this->max = $cuboid->getMax();
	}

	public function nextVector(){
		velociraptor:
		$this->a++;
		if($this->a > $this->maxA){
			$this->a = $this->minA;
			++$this->b;
			if($this->b > $this->maxB){
				$this->phase++;
				if($this->phase >= 6){
					throw new EndOfBlockStreamException;
				}
				$this->phasalRewind();
				goto velociraptor;
			}
		}
		return $this->toVector();
	}

	private function toVector() : Vector3{
		$vector = new Vector3;
		switch($this->phase){
			case 0:
			case 1:
				$vector->x = $this->a;
				$vector->y = $this->b;
				$vector->z = $this->fixed;
				return $vector;
			case 2:
			case 3:
				$vector->y = $this->a;
				$vector->z = $this->b;
				$vector->x = $this->fixed;
				return $vector;
			case 4:
			case 5:
				$vector->z = $this->a;
				$vector->x = $this->b;
				$vector->y = $this->fixed;
				return $vector;
		}
		throw new \UnexpectedValueException("Unexpected phase $this->phase");
	}

	private function phasalRewind(){
		switch($this->phase){
			case 0:
			case 1:
				$this->a = $this->minA = $this->min->x;
				$this->maxA = $this->max->x;
				$this->b = $this->min->y;
				$this->maxB = $this->max->y;
				$this->fixed = (($this->phase & 1) ? $this->max : $this->min)->z;
				break;
			case 2:
			case 3:
				$this->a = $this->minA = $this->min->y;
				$this->maxA = $this->max->y;
				$this->b = $this->min->z;
				$this->maxB = $this->max->z;
				$this->fixed = (($this->phase & 1) ? $this->max : $this->min)->x;
				break;
			case 4:
			case 5:
				$this->a = $this->minA = $this->min->z;
				$this->maxA = $this->max->z;
				$this->b = $this->min->x;
				$this->maxB = $this->max->x;
				$this->fixed = (($this->phase & 1) ? $this->max : $this->min)->y;
				break;
		}
		throw new \UnexpectedValueException("Unexpected phase " . $this->phase);
	}

	protected function rewind(){
		$this->phase = 0;
		$this->a = $this->min->x - 1;
		$this->b = $this->min->y;
		$this->fixed = $this->min->z;
	}

	public function getLevel() : Level{
		return $this->cuboid->getLevel();
	}

	public function maxSize() : int{
		$size = $this->max->subtract($this->min);
		return ($size->x + 2) * ($size->y + $size->z + 2) * 2 + ($size->y) * ($size->z) * 2;
	}
}
