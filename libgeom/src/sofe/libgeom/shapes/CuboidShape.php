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

namespace sofe\libgeom\shapes;

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;
use sofe\libgeom\LibgeomBinaryStream;
use sofe\libgeom\Shape;

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

	public function getSolidStream(Vector3 $vector) : \Generator{
		for($vector->x = $this->min->x; $vector->x <= $this->max->x; ++$vector->x){
			for($vector->y = $this->min->y; $vector->y <= $this->max->y; ++$vector->y){
				for($vector->z = $this->min->z; $vector->z <= $this->max->z; ++$vector->z){
					yield true;
				}
			}
		}
	}

	public function getShallowStream(Vector3 $vector, float $padding, float $margin) : \Generator{
		for($l = 1 - (int) round($padding); $l <= (int) round($margin); ++$l){
			$i = 0;
			for($vector->y = $this->min->y - $l; $i < 2; $vector->y = $this->max->y + $l, ++$i){
				for($vector->x = $this->min->x - $l; $vector->x <= $this->max->x + $l; ++$vector->x){
					for($vector->z = $this->min->z - $l; $vector->z <= $this->max->z + $l; ++$vector->z){
						yield true;
					}
				}
			}
			for($vector->z = $this->min->z - $l; $i < 4; $vector->z = $this->max->z + $l, ++$i){
				for($vector->x = $this->min->x - $l; $vector->x <= $this->max->x + $l; ++$vector->x){
					for($vector->y = $this->min->y - $l + 1; $vector->y <= $this->max->x + $l - 1; ++$vector->y){
						yield true;
					}
				}
			}
			for($vector->x = $this->min->x- $l; $i < 6; $vector->x = $this->max->x + $l, ++$i){
				for($vector->z = $this->min->z - $l + 1; $vector->z <= $this->max->z + $l - 1; ++$vector->z){
					for($vector->y = $this->min->y - $l + 1; $vector->y <= $this->max->x + $l - 1; ++$vector->y){
						yield true;
					}
				}
			}
		}
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

	public function marginalDistance(Vector3 $vector) : float{
		$diffs = [
			$this->max->x - $vector->x,
			$vector->x - $this->min->x,
			$this->max->y - $vector->y,
			$vector->y - $this->min->y,
			$this->max->z - $vector->z,
			$vector->z - $this->min->z,
		];
		$min = min($diffs);
		if($min >= 0){
			return -$min;
		}
		$m = new Vector3;
		$m->x = min($this->max->x, max($this->min->x, $vector->x));
		$m->y = min($this->max->y, max($this->min->y, $vector->y));
		$m->z = min($this->max->z, max($this->min->z, $vector->z));
		return $vector->distance($m);
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
