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

namespace sofe\libgeom\shape;

use pocketmine\math\Vector3;
use pocketmine\Server;

abstract class Shape extends SoftLevelStorage{
	private $estimatedSize;

	public abstract function isInside(Vector3 $vector) : bool;

	public function getEstimatedSize() : int{
		return $this->estimatedSize ?? ($this->estimatedSize = $this->estimateSize());
	}

	protected abstract function estimateSize() : int;

	public abstract function getSolidStream() : BlockStream;

	public abstract function getShallowStream(float $padding, float $margin) : BlockStream;

	/**
	 * Returns an array of the Level::chunkHash()s of the chunks involved with this shape
	 *
	 * @return int[]
	 */
	public abstract function getChunksInvolved() : array;

	/**
	 * By calling <T extends Shape> T::fromBinary(), a new T instance should be created.
	 *
	 * This method must be overridden in all non-abstract subclasses.
	 *
	 * @param Server              $server
	 * @param LibgeomBinaryStream $stream
	 *
	 * @return Shape
	 * @throws \Exception
	 */
	public static function fromBinary(/** @noinspection PhpUnusedParameterInspection */
		Server $server, LibgeomBinaryStream $stream) : Shape{
		throw new \Exception("Unimplemented method " . static::class . "::fromBinary(\$binary)");
	}

	public abstract function toBinary(LibgeomBinaryStream $stream);
}
