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
use pocketmine\Server;
use sofe\libgeom\shape\BatchBlockStream;
use sofe\libgeom\shape\BlockStream;
use sofe\libgeom\shape\LibgeomBinaryStream;
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

	public function isInside(Vector3 $vector) : bool{
		return
			$this->min->x <= $vector->x and $vector->x <= $this->max->x and
			$this->min->y <= $vector->y and $vector->y <= $this->max->y and
			$this->min->z <= $vector->z and $vector->z <= $this->max->z;
	}

	protected function estimateSize() : int{
		$diff = $this->max->subtract($this->min);
		return ($diff->x + 1) * ($diff->y + 1) * ($diff->z + 1);
	}

	public function getSolidStream() : BlockStream{
		return new CuboidSolidStream($this);
	}

	public function getShallowStream(float $padding, float $margin) : BlockStream{
		for($i = 1 - (int) round($padding); $i <= (int) round($margin); ++$i){
			$children[] = new CuboidSimpleShallowStream($this);
		}
		if(!isset($children)){
			throw new \InvalidArgumentException("Empty shallow stream");
		}
		return new BatchBlockStream($children);
	}

	public function recalcMinMax(){
		if(isset($this->from, $this->to)){
			$this->min = new Vector3(
				min($this->from->x, $this->to->x),
				min($this->from->y, $this->to->y),
				min($this->from->z, $this->to->z));
			$this->max = new Vector3(
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

	public function getChunksInvolved() : array{
		$chunks = [];
		for($X = $this->min->x >> 4; $X <= $this->max->x >> 4; ++$X){
			for($Z = $this->min->z >> 4; $Z <= $this->max->z >> 4; ++$Z){
				$chunks[] = Level::chunkHash($X, $Z);
			}
		}
		return $chunks;
	}


	public static function fromBinary(Server $server, LibgeomBinaryStream $stream) : Shape{
		$from = new Vector3();
		$to = new Vector3();
		$stream->getBlockPosition($from->x, $from->y, $from->z);
		$stream->getBlockPosition($to->x, $to->y, $to->z);
		return new CuboidShape($from, $to, $server->getLevelByName($stream->getString()));
	}

	public function toBinary(LibgeomBinaryStream $stream){
		$stream->putBlockPosition($this->from->x, $this->from->y, $this->from->z);
		$stream->putBlockPosition($this->to->x, $this->to->y, $this->to->z);
		$stream->putString($this->getLevel()->getFolderName());
	}
}
