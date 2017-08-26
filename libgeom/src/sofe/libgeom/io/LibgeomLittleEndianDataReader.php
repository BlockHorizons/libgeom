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

use sofe\toomuchbuffer\LittleEndianDataReader;
use sofe\toomuchbuffer\StreamInputStream;
use sofe\toomuchbuffer\StringInputStream;

class LibgeomLittleEndianDataReader extends LittleEndianDataReader implements LibgeomDataReader{
	public static function fromFile(string $file) : LibgeomLittleEndianDataReader{
		return new LibgeomLittleEndianDataReader(new StreamInputStream(fopen($file, "rb")));
	}

	public static function fromBuffer(string $buffer) : LibgeomLittleEndianDataReader{
		return new LibgeomLittleEndianDataReader(new StringInputStream($buffer));
	}

	public function readString() : string{
		return $this->stream->read($this->readVarInt(false));
	}

	public function readBlockPosition(&$x, &$y, &$z, bool $signed = false){
		$x = $this->readVarInt();
		$y = $this->readVarInt($signed);
		$z = $this->readVarInt();
	}

	/**
	 * @param float &$x
	 * @param float &$y
	 * @param float &$z
	 */
	public function readVector3f(&$x, &$y, &$z){
		$x = round($this->readFloat(), 4);
		$y = round($this->readFloat(),4);
		$z = round($this->readFloat(),4);
	}
}
