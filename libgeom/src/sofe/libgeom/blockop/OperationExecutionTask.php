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

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;

class OperationExecutionTask extends PluginTask{
	/** @var BlockOperationManager */
	private $manager;
	/** @var BlockOperation|null */
	private $activeOperation = null;
	/** @var UserHistory|null */
	private $historyOfActiveOperation = null;
	/** @var float */
	private $maxTickTime;

	public function __construct(Plugin $plugin, BlockOperationManager $manager, float $maxTickRatio = 0.2){
		parent::__construct($plugin);
		$this->manager = $manager;
		$this->maxTickTime = 0.05 * $maxTickRatio;
	}

	public function onRun($currentTick){
		$start = microtime(true);
		while(microtime(true) - $start < $this->maxTickTime){
			$this->doCycle();
			if($this->activeOperation === null){
				break;
			}
		}
	}

	public function doCycle(){
		if($this->activeOperation === null){
			foreach($this->manager->activeHistories as $history){
				if($history->getExecuting() !== null){
					$this->historyOfActiveOperation = $history;
					$this->activeOperation = $history->getExecuting();
					$this->activeOperation->startOperation($this->manager);
					break;
				}
			}
			if($this->activeOperation === null){
				return;
			}
		}
		if(!$this->activeOperation->operateNext()){
			$this->activeOperation->stopOperation();
			$this->activeOperation = null;
			$this->historyOfActiveOperation->onExecutionDone();
			$this->historyOfActiveOperation = null;
		}
	}
}
