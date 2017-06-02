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
use pocketmine\math\Vector3;
use pocketmine\plugin\Plugin;

class BlockOperationManager extends FileBinaryStream{
	/** @var string */
	private $tmpFile;
	/** @var int */
	private $taskId;

	/** @var int[][] */
	private $occupiedSections = [];
	private $freedSections = [];

	private $lock;
	private $selection;

	/** @var UserHistory[] */
	public $activeHistories = [];

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

	public function select(int $from, int $to){
		if(isset($this->selection)){
			throw new \InvalidStateException("Deselect first");
		}
		$this->selection = [$from, $to];
	}

	public function deselect(){
		unset($this->selection);
	}

	private function validateSelection(int $offset){
		$offset += $this->getOffset();
		return $this->selection[0] <= $offset and $offset < $this->selection[1];
	}

	/**
	 * Allocate memory for writing. Before anything is written, the allocated memory should contain null bytes only.
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
				fseek($this->fh, $start);
				for($ll = $len; $ll > 4000; $ll -= 4000){
					fwrite($this->fh, str_repeat("\0", 4000));
				}
				fwrite($this->fh, str_repeat("\0", $ll));
				fseek($this->fh, $start);
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

	public function mseek(string $key, int $id) : array{
		$this->validateLock($key);

		if(!isset($this->occupiedSections[$id])){
			return [-1, -1];
		}
		list($start, $end) = $this->occupiedSections[$id];
		fseek($this->fh, $start);

		$opId = $this->getInt();
		assert($opId === $id, "Operation not stored correctly");
		$levelId = $this->getInt(); // why did I even store this?
		return [$this->getOffset(), $end];
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

	public function seekStep(string $key, int $startOffset, int $step){
		$this->validateLock($key);
		fseek($this->fh, $startOffset + 8 + $step * BlockOperationManager::BYTES_PER_STEP);
	}

	public function readStep(string $key, Vector3 &$pos = null, Block &$from = null, Block &$to = null) : bool{
		$this->validateLock($key);
		if(!$this->getBool()){
			return false;
		}
		$pos = new Vector3($this->getInt(), $this->getShort(), $this->getInt());
		$from = Block::get($this->getInt(), $this->getByte());
		$to = Block::get($this->getInt(), $this->getByte());
		return true;
	}

	public function writeStep(string $key, Vector3 $pos, Block $from, Block $to){
		$this->validateLock($key);
		// flag: 1 byte
		$this->putBool(true);
		// position: 10 bytes
		$this->putInt($pos->x);
		$this->putShort($pos->y);
		$this->putInt($pos->z);
		// from: 5 bytes
		$this->putInt($from->getId());
		$this->putByte($from->getDamage());
		// to: 5 bytes
		$this->putInt($to->getId());
		$this->putByte($to->getDamage());
		// total: 21 bytes
	}

	public function get($len){
		if(!isset($this->lock)){
			throw new \InvalidStateException("Lock the manager before reading");
		}
		$this->validateSelection($len);
		return parent::get($len);
	}

	public function put($str){
		if(!isset($this->lock)){
			throw new \InvalidStateException("Lock the manager before reading");
		}
		$this->validateSelection(strlen($str));
		parent::put($str); // TODO: Change the autogenerated stub
	}

	const BYTES_PER_STEP = 21;
}
