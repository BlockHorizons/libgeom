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

namespace sofe\libgeom\shape;

use pocketmine\level\Level;

class BatchBlockStream extends BlockStream{
	/** @var BlockStream[] */
	private $children;
	private $i = 0;

	public function __construct(array $children){
		$this->children = array_values($children);
		if(count($this->children) === 0){
			throw new \UnexpectedValueException("Empty batch block stream");
		}
		$this->rewind();
	}

	/** @noinspection PhpInconsistentReturnPointsInspection */
	public function nextVector(){
		while(true){
			try{
				return $this->children[$this->i]->nextVector();
			}catch(EndOfBlockStreamException $e){
				++$this->i;
				if(!isset($this->children[$this->i])){
					throw $e; // or new EndOfBlockStreamException, doesn't matter
				}
				continue;
			}
		}
	}

	protected function rewind(){
		$this->i = 0;
		foreach($this->children as $child){
			$child->rewind();
		}
	}

	public function getLevel() : Level{
		return $this->children[0]->getLevel();
	}

	public function maxSize() : int{
		$sum = 0;
		foreach($this->children as $child){
			$sum += $child->maxSize();
		}
		return $sum;
	}
}
