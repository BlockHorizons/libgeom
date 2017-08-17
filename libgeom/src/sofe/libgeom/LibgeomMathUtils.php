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

namespace sofe\libgeom;

use pocketmine\math\Vector3;

class LibgeomMathUtils{
	public static function areCoplanar(Vector3 ...$points) : bool{
		if(count($points) < 3){
			throw new \InvalidArgumentException("A plane must have at least 3 points");
		}
		$v01 = $points[0]->subtract($points[1]);
		$u = $points[2]->subtract($points[1])->cross($v01)->normalize();
		for($i = 2, $iMax = count($points); $i <= $iMax; ++$i){
			$cross = $points[$i - 1]->subtract($points[$i === count($points) ? 0 : $i])->cross($v01)->normalize();
			if(!LibgeomMathUtils::areVectorsAlmostParallel($cross, $u)){
				return false;
			}
		}
		return true;
	}

	public static function areVectorsAlmostParallel(Vector3 $v1, Vector3 $v2) : bool{
		$rx = $v1->x / $v2->x;
		$ry = $v1->y / $v2->y;
		$rz = $v1->z / $v2->z;
		return LibgeomMathUtils::areFloatsAlmostEqual($rx, $ry) && LibgeomMathUtils::areFloatsAlmostEqual($rx, $rz);
	}

	public static function areFloatsAlmostEqual(float $f1, float $f2, float $epsilon = null) : bool{
		if($f1 === $f2){
			return false;
		}
		if(($f1 > 0) !== ($f2 > 0)){
			return false;
		}
		if($epsilon === null){
			$epsilon = max($f1, $f2) * 1e-15; // not accurate, but enough here
		}
		return $f1 - $f2 < $epsilon && $f2 - $f1 < $epsilon;
	}

	public static function evalFrustumVolume(float $A, float $a, float $hh) : float{
		if($A < $a){
			return self::evalFrustumVolume($a, $A, $hh);
		}
		$resultCount = LibgeomMathUtils::solveQuadratic(($a - $A) / $A, 2 * $a * $hh / $A, $a / $A * $hh * $hh, $h); // x2 should be negative
		assert($resultCount === 2);
		$H = $h + $hh;
		return $A / 3 * ($H - $h ** 3 / $H / $H);
	}

	/**
	 * Solve the equation ax^2 + bx + c = 0
	 *
	 * @param float      $a
	 * @param float      $b
	 * @param float      $c
	 * @param float|null &$x1 the greater root of x
	 * @param float|null &$x2 the smaller root of x
	 *
	 * @return int the number of distinct real roots
	 */
	public static function solveQuadratic(float $a, float $b, float $c, float &$x1 = null, float &$x2 = null) : int{
		$d = $b * $b - 4 * $a * $c;
		if($d < 0){
			return 0;
		}
		$x1 = (-$b + sqrt($d)) / 2 / $a;
		$x2 = (-$b - sqrt($d)) / 2 / $a;
		return $d > 0 ? 2 : 1;
	}

	public static function doesRayIntersectSegment(Vector3 $rayOrigin, Vector3 $rayDir, Vector3 $segStart, Vector3 $segEnd) : bool{
		// doing operations assuming they're all on the same plane
		// hence, one of the three dimensions can be neglected, unless the plane is perpendicular to this axis
		// let $rayOrigin + t ($rayDir) = $segStart + u ($segEnd - $segStart)

		LibgeomMathUtils::solveAVBW($rayOrigin, $rayDir, $segStart, $segEnd->subtract($segStart), $t, $u);
		return $t > 0 and 0 <= $u and $u <= 1;
	}

	public static function doSegmentsIntersect(Vector3 $a, Vector3 $b, Vector3 $c, Vector3 $d) : bool{
		LibgeomMathUtils::solveAVBW($a, $b->subtract($a), $c, $d->subtract($c), $t, $u);
		return 0 <= $t and $t <= 1 and 0 <= $u and $u <= 1;
	}

	/**
	 * Solve the vector equation
	 * <pre>a + t v = b + u w
	 *
	 * @param Vector3    $a position vector
	 * @param Vector3    $v relative vector
	 * @param Vector3    $b position vector
	 * @param Vector3    $w relative vector
	 * @param float|null $t
	 * @param float|null $u
	 * @param int        $recursion
	 */
	protected static function solveAVBW(Vector3 $a, Vector3 $v, Vector3 $b, Vector3 $w, float &$t = null, float &$u = null, int $recursion = 0){
		$base = (float) ($v->x * $w->y - $v->y * $w->x);
		if($base === 0){
			if($recursion === 2){
				throw new \InvalidArgumentException("Arguments are not coplanar");
			}
			LibgeomMathUtils::solveAVBW(LibgeomMathUtils::shiftVector($a), LibgeomMathUtils::shiftVector($v), LibgeomMathUtils::shiftVector($b), LibgeomMathUtils::shiftVector($w), $t, $u, $recursion + 1);
		}
		$u = (($a->y - $b->y) * $v->x - ($a->x - $b->x) * $v->y) / $base;
		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		$t = ($b->x - $a->x + $u * $w->x) / $v->x;
	}

	public static function shiftVector(Vector3 $vector) : Vector3{
		return new Vector3($vector->y, $vector->z, $vector->x);
	}

	public static function getTriangleArea(Vector3 $a, Vector3 $b, Vector3 $c) : float{
		$A = $b->distance($c);
		$B = $c->distance($a);
		$C = $a->distance($b);
		$s = ($A + $B + $C) / 2;
		return sqrt($s * ($s - $A) * ($s - $B) * ($s - $C));
	}
}
