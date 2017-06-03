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

class LazyStreamsShallowStream extends LazyStreamsSolidStream{
	/** @var float */
	private $padding, $margin;
	/** @var int */
	private $maxSize;

	public function __construct(LazyStreamsShape $shape, float $padding, float $margin, float $maxSize){
		parent::__construct($shape);
		$this->padding = $padding;
		$this->margin = $margin;
		$this->maxSize = (int) ceil($maxSize);
	}

	protected function validateVector(Vector3 $vector) : bool{
		$dist = $this->shape->marginalDistance($vector);
		return -$this->padding <= $dist and $dist <= $this->margin - 1;
	}

	public function maxSize() : int{
		return $this->maxSize;
	}
}
