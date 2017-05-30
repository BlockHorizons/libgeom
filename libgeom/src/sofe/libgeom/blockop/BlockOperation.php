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

namespace sofe\libgeom\blockop;

use sofe\libgeom\shape\BlockStream;

class BlockOperation{
	private static $nextId = 0;

	/** @var int */
	private $id;
	/** @var BlockStream|null */
	private $stream; // TODO unset this upon first completion
	/** @var int */
	private $step = 0;
	/** @var int */
	private $levelId;
	/** @var bool */
	private $forwards = true;

	public static function getNextId() : int{
		return BlockOperation::$nextId++;
	}

	public function __construct(BlockStream $stream){
		$this->stream = $stream;
		$this->id = BlockOperation::getNextId();
		$this->levelId = $stream->getLevel()->getId();
	}

	public function getId(){
		return $this->id;
	}

	public function getLevelId() : int{
		return $this->levelId;
	}

	public function setForwards(bool $forwards) : BlockOperation{
		$this->forwards = $forwards;
		return $this;
	}

	public function isForwards() : bool{
		return $this->forwards;
	}

	public function sizeRequired() : int{
		return 4 // operation ID
			+ 4 // level ID
			+ $this->stream->maxSize() * (
				5 * 3 // position
				+ 5 // undo blockId (before)
				+ 1 // undo blockData (before)
				+ 5 // redo blockId (after)
				+ 1 // redo blockData (after)
			);
	}
}
