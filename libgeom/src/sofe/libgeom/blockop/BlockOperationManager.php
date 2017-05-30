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

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\plugin\Plugin;
use sofe\libgeom\FileBinaryStream;

class BlockOperationManager extends FileBinaryStream{
	/** @var string */
	private $tmpFile;
	/** @var int */
	private $taskId;

	/** @var int[][] */
	private $occupiedSections = [];
	private $freedSections = [];

	private $lock;

	public function __construct(Plugin $plugin, string $tmpFile){
		$this->taskId = $plugin->getServer()->getScheduler()->scheduleRepeatingTask(new OperationExecutionTask($this), 1)->getTaskId();
		$this->tmpFile = $tmpFile;
		parent::__construct(fopen($this->tmpFile, "x+b"));
	}

	public function dispose(Plugin $plugin){
		$plugin->getServer()->getScheduler()->cancelTask($this->taskId);
	}


	public function lock() : string{
		if(isset($this->lock)){
			throw new \InvalidStateException("OperationManager already locked");
		}
		return $this->lock = random_bytes(4);
	}

	public function unlock(string $key){
		if(!isset($this->lock)){
			throw new \InvalidStateException("Nothing to unlock");
		}
		if($this->lock !== $key){
			throw new \InvalidStateException("Concurrent access to OperationManager");
		}
		unset($this->lock);
	}

	private function validateLock(string $key){
		if(!isset($this->lock)){
			throw new \InvalidStateException("OperationManager must be locked before moving the pointer");
		}
		if($key !== $this->lock){
			throw new \InvalidStateException("Concurrent access to OperationManager");
		}
	}

	/**
	 * Allocate memory for writing. Before anything is written, the allocated memory may contain any (probably unreasonable) data.
	 *
	 * @param string $key
	 * @param int    $id
	 * @param int    $len
	 *
	 * @return int
	 */
	public function malloc(string $key, int $id, int $len) : int{
		$this->validateLock($key);

		if($len <= 0){
			throw new \InvalidArgumentException();
		}

		foreach($this->freedSections as $key => list($start, $end)){
			if($end - $start >= $len){
				// chosen!
				if($end === $start + $len){
					unset($this->freedSections[$key]);
				}else{
					$this->freedSections[$key][0] = $start + $len;
				}
				$this->occupiedSections[$id] = [$start, $start + $len];
				fseek($this->fh, $len - 1);
				return $start;
			}
		}

		fseek($this->fh, $len - 1, SEEK_END);
		fwrite($this->fh, "\0");
		fseek($this->fh, -$len, SEEK_CUR);

		$start = $this->getOffset();
		$this->occupiedSections[$id] = [$start, $start + $len];
		return $start;
	}

	public function mfree(int $id){
		// no need to validate lock because this is irrelevant to the current file pointer

		if(!isset($this->occupiedSections[$id])){
			throw new \InvalidStateException("Operation is already freed");
		}
		list($start, $end) = $this->occupiedSections[$id];
		unset($this->occupiedSections[$id]);

		$this->freedSections[] = [$start, $end];
		$this->linkFreedSections();
	}

	private function linkFreedSections(){
		// no need to validate lock because this is irrelevant to the current file pointer
		$fs = $this->freedSections;
		usort($fs, function($a, $b){
			return $a[0] <=> $b[0];
		});
		$this->freedSections = [];
		foreach($fs as $pair){
			if(isset($last) and $last[0] <= $pair[0] and $pair[0] <= $last[1]){
				$last[1] = max($last[1], $pair[1]);
			}else{
				$this->freedSections[] =& $pair;
				$last =& $pair;
			}
		}
	}

	/**
	 * Allocate memory to store the stacks for an operation.
	 *
	 * @param string         $key
	 * @param BlockOperation $operation
	 */
	public function preOperationStore(string $key, BlockOperation $operation){
		$this->malloc($key, $operation->getId(), $operation->sizeRequired());
		$this->putInt($operation->getId());
		$this->putInt($operation->getLevelId());
	}


	private function readPosition(string $key, Level $level = null) : Position{
		$this->validateLock($key);
		return new Position($this->getVarInt(), $this->getUnsignedVarInt(), $this->getVarInt(), $level);
	}

	private function writeVector(string $key, Vector3 $vector){
		$this->validateLock($key);
		$this->putVarInt($vector->x);
		$this->putUnsignedVarInt($vector->y);
		$this->putVarInt($vector->z);
	}

	public function readPositionedBlock(string $key, Level $level = null) : Block{
		$this->validateLock($key);
		$pos = $this->readPosition($level);
		return Block::get($this->getUnsignedVarInt(), $this->getByte(), $pos);
	}

	public function writePositionedBlock(string $key, Block $block){
		$this->validateLock($key);
		$this->writeVector($key, $block);
		$this->putUnsignedVarInt($block->getId());
		$this->putByte($block->getDamage());
	}
}
