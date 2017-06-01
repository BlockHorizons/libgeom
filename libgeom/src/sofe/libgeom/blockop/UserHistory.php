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
				throw new \RuntimeException("Nothing to undo");
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
			throw new \RuntimeException("Nothing to undo");
		}else{
			// B from $undoStack to $toUndo
			assert(count($this->toRedo) === 0);
			array_unshift($this->toUndo, array_pop($this->undoStack));
		}
	}

	public function redo(){
		if($this->executing === null){
			assert(count($this->toUndo) === 0, count($this->toRedo) === 0);
			// H from $redoStack to $executing ($toRedo is skipped since it's asserted empty)
			if(count($this->redoStack) === 0){
				throw new \RuntimeException("Nothing to redo");
			}
			$this->executing = array_shift($this->redoStack);
			$this->executing->setForwards(true);
		}elseif(count($this->toUndo) > 0){
			// C from $toUndo to $undoStack
			array_push($this->undoStack, array_shift($this->toUndo));
		}elseif(!$this->executing->isForwards()){
			// toggle E
			$this->executing->setForwards(true);
		}elseif(count($this->redoStack) === 0){
			throw new \RuntimeException("Nothing to redo");
		}else{
			// $redoStack to $toRedo
			assert(count($this->toUndo) === 0);
			array_push($this->toRedo, array_shift($this->redoStack));
		}
	}

	public function addOperation(BlockOperation $operation){
		$this->redoStack = [];
		if($this->executing === null){
			$this->executing = $operation;
		}else{
			// TODO IMPORTANT: confirm the logic: what if $toUndo is not empty?
			$this->toRedo[] = $operation;
		}
	}

	public function onExecutionDone(){
		if($this->executing->isForwards()){
			// E from $executing to $undoStack
			array_push($this->undoStack, $this->executing);
			$this->executing = null;
		}else{
			// E from $executing to $redoStack
			array_unshift($this->redoStack, $this->executing);
			$this->executing = null;
		}

		if(count($this->toUndo) > 0){
			// D from $toUndo to $executing
			$this->executing = array_pop($this->toUndo);
			$this->executing->setForwards(false);
		}elseif(count($this->toRedo) > 0){
			// F from $toRedo to $executing
			$this->executing = array_shift($this->toRedo);
			$this->executing->setForwards(true);
		}
	}

	public function getUndoStack() : array{
		return $this->undoStack;
	}

	public function getToUndo() : array{
		return $this->toUndo;
	}

	public function getExecuting() : BlockOperation{
		return $this->executing;
	}

	public function getToRedo() : array{
		return $this->toRedo;
	}

	public function getRedoStack() : array{
		return $this->redoStack;
	}
}
