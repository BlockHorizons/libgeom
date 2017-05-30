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

class UserHistory{
	/** @var BlockOperation[] */
	private $undoStack = []; // operation A, operation B
	/** @var BlockOperation[] */
	private $toUndo = []; // operation C, operation D
	/** @var BlockOperation */
	private $executing = null; // operation E
	/** @var BlockOperation[] */
	private $toRedo = []; // operation F, operation G
	/** @var BlockOperation[] */
	private $redoStack = []; // operation H, operation I

	// possible fullness combinations of $toUndo, $executing and $toRedo:
	// $toUndo | $executing | $toRedo
	//   full  |     set    |  empty
	//  empty  |     set    |   full
	//  empty  |     set    |  empty
	//  empty  |    null    |  empty

	public function undo(){
		if($this->executing === null){
			assert(count($this->toUndo) === 0 && count($this->toRedo) === 0);
			// B from $undoStack to $executing ($toUndo is skipped since it's asserted empty)
			if(count($this->undoStack) === 0){
				throw new \RuntimeException("Nothing to redo");
			}
			$this->executing = array_pop($this->undoStack);
			$this->executing->setForwards(false);
		}elseif(count($this->toRedo) > 0){
			// G from $toRedo to $redoStack
			array_unshift($this->redoStack, array_pop($this->toRedo));
		}elseif($this->executing->isForwards()){
			// toggle E
			$this->executing->setForwards(false);
		}elseif(count($this->undoStack) === 0){
			throw new \RuntimeException("Nothing to redo");
		}else{
			// B from $undoStack to $toUndo
			array_unshift($this->toUndo, array_pop($this->undoStack));
		}
	}

	public function redo(){
		if($this->executing === null){
			assert(count($this->toUndo) === 0, count($this->toRedo) === 0);
			//
		}
	}
}
