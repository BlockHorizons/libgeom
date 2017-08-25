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

namespace sofe\libgeom\io;

use sofe\toomuchbuffer\BigEndianDataWriter;

class LibgeomBigEndianDataWriter extends BigEndianDataWriter implements LibgeomDataWriter{
	public function writeString(string $string){
		$this->writeVarInt(strlen($string), false);
		$this->stream->write($string);
	}

	public function writeBlockPosition(int $x, int $y, int $z, bool $signed = false){
		$this->writeVarInt($x);
		$this->writeVarInt($y, $signed);
		$this->writeVarInt($z);
	}

	public function writeVector3f(float $x, float $y, float $z){
		$this->writeFloat($x);
		$this->writeFloat($y);
		$this->writeFloat($z);
	}
}
