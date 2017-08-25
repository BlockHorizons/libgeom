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

use sofe\toomuchbuffer\DataReader;

interface LibgeomDataReader extends DataReader{
	public function readString() : string;

	public function readBlockPosition(&$x, &$y, &$z, bool $signed = false);

	public function readVector3f(&$x, &$y, &$z);
}
