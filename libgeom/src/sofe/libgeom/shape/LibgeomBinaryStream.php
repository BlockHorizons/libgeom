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

namespace sofe\libgeom\shape;

use pocketmine\utils\BinaryStream;

/**
 * This class contains some methods copied from DataPacket from PocketMine
 */
class LibgeomBinaryStream extends BinaryStream{
	/**
	 * Reads an block position with unsigned Y coordinate.
	 * @param int &$x
	 * @param int &$y
	 * @param int &$z
	 */
	public function getBlockPosition(&$x, &$y, &$z){
		$x = $this->getVarInt();
		$y = $this->getUnsignedVarInt();
		$z = $this->getVarInt();
	}

	/**
	 * Writes a block position with unsigned Y coordinate.
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 */
	public function putBlockPosition(int $x, int $y, int $z){
		$this->putVarInt($x);
		$this->putUnsignedVarInt($y);
		$this->putVarInt($z);
	}

	/**
	 * Reads a block position with a signed Y coordinate.
	 * @param int &$x
	 * @param int &$y
	 * @param int &$z
	 */
	public function getSignedBlockPosition(&$x, &$y, &$z){
		$x = $this->getVarInt();
		$y = $this->getVarInt();
		$z = $this->getVarInt();
	}

	/**
	 * Writes a block position with a signed Y coordinate.
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 */
	public function putSignedBlockPosition(int $x, int $y, int $z){
		$this->putVarInt($x);
		$this->putVarInt($y);
		$this->putVarInt($z);
	}

	/**
	 * Reads a floating-point vector3 rounded to 4dp.
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 */
	public function getVector3f(&$x, &$y, &$z){
		$x = $this->getRoundedLFloat(4);
		$y = $this->getRoundedLFloat(4);
		$z = $this->getRoundedLFloat(4);
	}

	/**
	 * Writes a floating-point vector3
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 */
	public function putVector3f(float $x, float $y, float $z){
		$this->putLFloat($x);
		$this->putLFloat($y);
		$this->putLFloat($z);
	}

	/**
	 * A buffer underflow check
	 *
	 * @param bool|int $len
	 *
	 * @return string
	 */
	public function get($len) : string{
		$ret = parent::get($len);
		if($ret === false || strlen($ret) < $len){
			throw new \UnderflowException();
		}
		return $ret;
	}
}
