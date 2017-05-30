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
use sofe\libgeom\shape\Shape;

class CuboidShape extends Shape{
	/** @var Vector3 */
	private $from, $to;

	/** @var Vector3 */
	private $min, $max;

	public function __construct(Vector3 $from, Vector3 $to, Level $level){
		$this->from = $from;
		$this->to = $to;
		$this->recalcMinMax();
		$this->setLevel($level);
	}

	public function estimateSize() : int{
		$diff = $this->max->subtract($this->min);
		return ($diff->x + 1) * ($diff->y + 1) * ($diff->z + 1);
	}

	public function getSolidStream() : BlockStream{
		return new CuboidSolidStream($this);
	}

	public function getShallowStream(float $padding, float $margin) : BlockStream{
		// TODO: Implement getShallowIterator() method.
	}

	public function recalcMinMax(){
		if(isset($this->from, $this->to)){
			$this->min = new Vector3(
				min($this->from->x, $this->to->x),
				min($this->from->y, $this->to->y),
				min($this->from->z, $this->to->z));
			$this->min = new Vector3(
				max($this->from->x, $this->to->x),
				max($this->from->y, $this->to->y),
				max($this->from->z, $this->to->z));
		}
	}

	public function getMin() : Vector3{
		return $this->min;
	}

	public function getMax() : Vector3{
		return $this->max;
	}

	public function getFrom() : Vector3{
		return $this->from;
	}

	public function getTo() : Vector3{
		return $this->to;
	}

	public function setFrom(Vector3 $from) : CuboidShape{
		$this->from = $from;
		$this->recalcMinMax();
		return $this;
	}

	public function setTo(Vector3 $to) : CuboidShape{
		$this->to = $to;
		$this->recalcMinMax();
		return $this;
	}
}
