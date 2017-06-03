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

namespace sofe\libgeom\shape\ellipsoid;

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use sofe\libgeom\shape\lazystreams\LazyStreamsShape;

class EllipsoidShape extends LazyStreamsShape{
	/** @var Vector3 */
	private $center;
	/** @var float */
	private $xrad, $yrad, $zrad;

	public function __construct(Level $level, Vector3 $center, float $xrad, float $yrad, float $zrad){
		$this->setLevel($level);
		$this->center = $center;
		if($xrad <= 0 or $yrad <= 0 or $zrad <= 0){
			throw new \InvalidArgumentException("Radii of ellipsoid must be positive");
		}
		$this->xrad = $xrad;
		$this->yrad = $yrad;
		$this->zrad = $zrad;
	}

	public function isInside(Vector3 $vector) : bool{
		$diff = $vector->subtract($this->center);
		return ($diff->x / $this->xrad) ** 2 + ($diff->y / $this->yrad) ** 2 + ($diff->z / $this->zrad) ** 2 <= 1;
	}

	public function marginalDistance(Vector3 $vector) : float{
		// Spherical equation of ellipsoid:
		// r^2 (cosθ sinϕ / a)² + (sinθ cosϕ / b)² + (cosθ / c)² = 1
		// Ref: Weisstein, Eric W. "Ellipsoid." From MathWorld--A Wolfram Web Resource. http://mathworld.wolfram.com/Ellipsoid.html
		// Hence, r = abc / √( (bc cosθ sinϕ)² + (ac sinθ cosϕ)² + (ab cosϕ)² )

		$diff = $vector->subtract($this->center)->abs();
		$spheric = clone $diff;
		$spheric->x /= $this->xrad;
		$spheric->y /= $this->yrad;
		$spheric->z /= $this->zrad;
		$spheric = $spheric->normalize();
		$radial = $diff->divide($spheric->length());
		// now $clone is the radial vector in the same direction as the diff vctor
		return $diff->length() - $radial->length();
	}

	public function estimateSize() : int{
		return 4 / 3 * M_PI * $this->xrad * $this->yrad * $this->zrad;
	}

	protected function lazyGetMinX() : float{
		return $this->center->x - $this->xrad;
	}

	protected function lazyGetMinY() : float{
		return $this->center->y - $this->yrad;
	}

	protected function lazyGetMinZ() : float{
		return $this->center->z - $this->zrad;
	}

	protected function lazyGetMaxX() : float{
		return $this->center->x + $this->xrad;
	}

	protected function lazyGetMaxY() : float{
		return $this->center->y + $this->yrad;
	}

	protected function lazyGetMaxZ() : float{
		return $this->center->z + $this->zrad;
	}
}
