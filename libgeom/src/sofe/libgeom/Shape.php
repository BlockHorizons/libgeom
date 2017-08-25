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

namespace sofe\libgeom;

use pocketmine\math\Vector3;
use pocketmine\Server;
use sofe\libgeom\io\LibgeomDataReader;
use sofe\libgeom\io\LibgeomDataWriter;
use sofe\toomuchbuffer\DataReader;

abstract class Shape extends SoftLevelStorage{
	private $estimatedSize;
	private $centerCache;

	/**
	 * Returns whether $vector is in this shape
	 *
	 * @param Vector3 $vector
	 *
	 * @return bool
	 */
	public abstract function isInside(Vector3 $vector) : bool;

	/**
	 * Returns the estimated number of blocks in this shape (as a solid)
	 *
	 * @return int
	 */
	public function getEstimatedSize() : int{
		return $this->estimatedSize ?? ($this->estimatedSize = $this->estimateSize());
	}

	protected abstract function estimateSize() : int;

	/**
	 * Returns the estimated number of blocks from a hollow stream
	 *
	 * @param float $padding
	 * @param float $margin
	 *
	 * @return int
	 */
	public abstract function getEstimatedSurfaceSize(float $padding, float $margin) : int;

	/**
	 * Returns a generator that yields all integer coordinates inside this shape.
	 *
	 * Pass a Vector3 (or its subclasses) object into this method. The properties of this Vector3 will be updated during
	 * generation. When true is yielded, the Vector3 is currently inside this shape. When false is yielded, the Vector3 MAY OR
	 * MAY NOT be inside this shape, but the generator pauses execution to allow the thread to sleep a while before continuing
	 * the generation.
	 *
	 * A PluginTask can be implemented like this to process this shape for approximately 1 millisecond every tick:
	 *
	 * ```php
	 * class Clazz extends PluginTask{
	 *     private $temporalVector;
	 *     private $generator;
	 *
	 *     public function __construct(Shape $shape){
	 *         // delegation to parent constructor omitted
	 *         $this->temporalVector = new Vector3;
	 *         $this->generator = $shape->getSolidStream($this->temporalVector);
	 *         $this->onRun(0);
	 *     }
	 *
	 *     public function onRun(int $t){
	 *         $start = microtime(true);
	 *         while(microtime(true) - $start < 1.0e-3){
	 *             $current = $this->generator->current();
	 *             if($current === null){ // generator finished
	 *                 return;
	 *             }elseif($current === true){ // $this->temporalVector is currently valid for processing
	 *                 process($this->temporalVector);
	 *             } // else, $current must === false, and nothing should be done with $this->temporalVector (it's at undefined state)
	 *             $current->next();
	 *         }
	 *         $this->getOwner()->scheduleDelayedTask($this, 1);
	 *     }
	 * }
	 * ```
	 *
	 * @param Vector3 $vector
	 *
	 * @return \Generator a generator that yields a boolean every time
	 */
	public abstract function getSolidStream(Vector3 $vector) : \Generator;

	/**
	 * Returns an iterator that iterates through all blocks on the surface of this shape.
	 *
	 * @see Shape::getSolidStream() on how to handle the yield values of the generator.
	 *
	 * @param Vector3 $vector
	 * @param float   $padding The thickness of the "surface" <em>inside</em> the shape
	 * @param float   $margin  The thickness of the "surface" <em>outside</em> the shape
	 *
	 * @return \Generator a generator that yields a boolean every time
	 */
	public abstract function getHollowStream(Vector3 $vector, float $padding, float $margin) : \Generator;

	/**
	 * Returns the vector's distance from the nearest point on the surface of the shape.
	 *
	 * The return value is negative if it is inside the vector, positive if outside the shape and zero if on the surface of the
	 * shape.
	 *
	 * @param Vector3 $vector
	 *
	 * @return float
	 */
	public abstract function marginalDistance(Vector3 $vector) : float;

	/**
	 * Returns the center point of the shape.
	 *
	 *
	 *
	 * @return Vector3|null
	 */
	public function getCenter(){
		if(!$this->isComplete()){
			return null;
		}
		return $this->centerCache ?? ($this->centerCache = $this->lazyGetCenter());
	}

	protected abstract function lazyGetCenter() : Vector3;

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
	 * @param LibgeomDataReader $stream
	 *
	 * @return Shape
	 * @throws \Exception
	 */
	public static function fromBinary(/** @noinspection PhpUnusedParameterInspection */
		Server $server, LibgeomDataReader $stream) : Shape{
		throw new \Exception("Unimplemented method " . static::class . "::fromBinary(\$binary)");
	}

	public abstract function toBinary(LibgeomDataWriter $stream);

	public abstract function getMinX() : int;

	public abstract function getMinY() : int;

	public abstract function getMinZ() : int;

	public abstract function getMaxX() : int;

	public abstract function getMaxY() : int;

	public abstract function getMaxZ() : int;

	public abstract function isComplete() : bool;

	protected function onDimenChanged(){
		unset($this->centerCache);
	}
}
