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

interface BlockReplacer{
	/**
	 * @param Block $block
	 *
	 * @return Block|null
	 */
	public function getReplacement(Block $block);
}
