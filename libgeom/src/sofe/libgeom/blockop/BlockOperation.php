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
use sofe\libgeom\shape\EndOfBlockStreamException;

class BlockOperation{
	private static $nextId = 0;

	/** @var int */
	private $id;
	/** @var BlockStream|null */
	private $stream; // TODO unset this upon first completion
	/** @var BlockReplacer|null */
	private $replacer;
	/** @var int */
	private $step = -1;
	/** @var int */
	private $levelId;
	/** @var bool */
	private $forwards = true;

	/** @var BlockOperationManager */
	private $manager;
	/** @var string */
	private $key;
	/** @var int */
	private $startOffset;
	/** @var bool */
	private $canReadDirectly = false;

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
		$this->step += $forwards ? -1 : 1;
		return $this;
	}

	public function isForwards() : bool{
		return $this->forwards;
	}

	public function sizeRequired() : int{
		return 4 // operation ID
			+ 4 // level ID
			+ $this->stream->maxSize() * BlockOperationManager::BYTES_PER_STEP;
	}

	public function startOperation(BlockOperationManager $manager){
		$this->key = $manager->lock();
		$this->manager = $manager;
		list($startOffset, $endOffset) = $manager->mseek($this->key, $this->id);
		if($startOffset === -1){
			return;
		}
		$this->manager->select($startOffset, $endOffset);
		$this->startOffset = $startOffset;
	}

	public function operateNext() : bool{
		if(!isset($this->manager, $this->key, $this->startOffset)){
			throw new \InvalidStateException("Call startOperation() before calling operateNext()!");
		}

		if($this->forwards){
			++$this->step;
		}else{
			--$this->step;
		}
		if($this->step === -1){
			return false;
		}
		if(!$this->forwards or !$this->canReadDirectly){
			$this->manager->seekStep($this->key, $this->startOffset, $this->step);
		}
		$hadStep = $this->manager->readStep($this->key, $pos, $rfrom, $rto);
		if($hadStep){
			$from = $rfrom;
			$to = $rto;
		}else{
			assert($this->forwards);
			if(isset($this->stream, $this->replacer)){
				try{
					$from = $this->stream->nextBlock();
				}catch(EndOfBlockStreamException $e){
					return false;
				}
				if($from === null){ // empty cycle, check timer and run again
					--$this->step;
					return true;
				}
				if($from !== null){ // not end of stream yet
					$to = $this->replacer->getReplacement($from);
					if($to !== null){
						$to->setComponents($from->x, $from->y, $from->z);
						$this->manager->seekStep($this->key, $this->startOffset, $this->step);
						$this->manager->writeStep($this->key, $from, $from, $to);
					}else{ // skip this cycle, may continue in the next cycle
						--$this->step;
						return true;
					}
				}else{
					return false;
				}
			}else{
				return false;
			}
		}
		$this->canReadDirectly = $this->forwards;

		$from->getLevel()->setBlock($from, $this->forwards ? $to : $from, false, false);
		return true;
	}

	public function stopOperation(){
		$this->manager->deselect();
		$this->manager->unlock($this->key);
		unset($this->manager, $this->key, $this->startOffset);
		$this->canReadDirectly = false;

		if(isset($this->stream, $this->replacer)){
			unset($this->stream, $this->replacer);
		}
	}
}
