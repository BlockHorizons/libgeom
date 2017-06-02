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

namespace sofe\libgeom\shape\frustum;

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use sofe\libgeom\shape\BlockStream;
use sofe\libgeom\shape\Shape;

/**
 * A FrustumShape refers to a conical frustum, a cylinder or a cone.
 * The shape can be <b>skewed</b>, <b>oblique</b> and <b>elliptical</b>.
 *
 * The axis of the shape refers to the line passing through the center of the base and the center of the top (for
 * cylinders and frustums), or the center of the base and the tip (for cones).
 * The normal of the shape refers to the line perpendicular to the plane of the base of the shape.
 *
 * <h3>Flexibility and limitations</h3>
 *
 * The shape can be oblique, i.e. the axis of the shape need not be parallel or perpendicular to the X/Y/Z axes.
 *
 * The shape can be skewed, i.e. the axis of the shape need not be perpendicular to the plane of the base.
 *
 * The shape can be elliptical, i.e. the base can be an ellipse (or a circle, which is a subset of ellipse). As for the
 * top, it can be a point (a circle with zero radius) or an ellipse (or a circle). If it is an ellipse, <b>The minimum
 * radius of the top must be either parallel or perpendicular to the minimum radius of the bottom</b>. The ratio of
 * minimum and maximum radii of the top ellipse may or may not be equal or reciprocal to that of the bottom ellipse.
 * <b>However, for frustums and cylinders, <b>the planes of the base and the top must be parallel</b>.
 *
 * <h3>Definition</h3>
 *
 * A FrustomShape is basically defined by its base and top centers.
 *
 * Then the left-right radii and the front-back radii of the top and base define the sizes of the top and base, where
 * the base radii must be positive but the top radii may be either both positive or both zero (for cones).
 *
 * Additionally, the orientation of the base and top ellipses is defined by the normal, which is a relative unit vector. This determines what is "horizontal" (parallel to base plane) and what is "vertical" (perpendicular to base plane) for
 * the FrustomShape. In addition, two relative unit vectors, rightDir and frontDir, determine the left-right and
 * front-back directions of the FrustomShape. By the right-hand rule, normal cross rightDir = frontDir, where normal
 * points upwards.
 */
class FrustumShape extends Shape{
	/** @var Vector3 */
	private $base, $top;
	/** @var Vector3 */
	private $baseRightCircum;
	/** @var float */
	private $baseRightRadius, $baseFrontRadius, $topRightRadius, $topFrontRadius;
	/** @var Vector3 */
	private $normal, $rightDir, $frontDir;

	/**
	 * @param Level   $level           the level that the shape is located in.
	 * @param Vector3 $base            the position vector representing the midpoint of the base ellipse
	 * @param Vector3 $baseRightCircum the position vector representing the intersection of the right radius and the circumference of the base ellipse
	 * @param float   $baseFrontRadius the front-back radius of the base ellipse, must be positive, may be smaller than |$baseRightCircum-$base|
	 * @param Vector3 $top             the position vector representing the midpoint of the top ellipse
	 * @param float   $topRightRadius  the left-right radius of the top ellipse, must be non-negative
	 * @param float   $topFrontRadius  the front-back radius of the top ellipse, may be smaller than $topMinRadius but parallel to $baseMaxRadius, but sign($topMaxRadius) === sign($topMinRadius)
	 * @param Vector3 $normal          the relative vector representing the orientation of line perpendicular to the plane of both ellipses, will be automatically normalized
	 */
	public function __construct(Level $level, Vector3 $base, Vector3 $baseRightCircum, float $baseFrontRadius, Vector3 $top, float $topRightRadius, float $topFrontRadius, Vector3 $normal){
		$this->setLevel($level);
		assert($normal->dot($base->subtract($baseRightCircum)) == 0, "baseMinCircum-base is not perpendicular to the normal");
		$this->base = $base;
		$this->baseRightCircum = $baseRightCircum;
		$baseMinRadiusLine = $baseRightCircum->subtract($this->base);
		$this->rightDir = $baseMinRadiusLine->normalize();
		$this->baseRightRadius = $baseMinRadiusLine->length();
		$this->baseFrontRadius = $baseFrontRadius;
		assert($this->baseRightRadius > 0);
		assert($this->baseFrontRadius > 0);
		$this->top = $top;
		assert(($topRightRadius <=> 0) === ($topFrontRadius <=> 0) and $topRightRadius >= 0);
		$this->topRightRadius = $topRightRadius;
		$this->topFrontRadius = $topFrontRadius;
		$this->normal = $normal->normalize();
		$this->frontDir = $this->normal->cross($this->rightDir); // taking normal as vertical and minDir as rightward, maxDir is forward
	}

	public function isInside(Vector3 $vector) : bool{
		// Put ^a = base, ^b = top, so ^a to $b is the axis of the frustum
		// Put ^p = position to test for, ^n = unit vector perpendicular to the ellipse planes
		// Let ^q be a point between ^a and ^b, where ^q - ^p is perpendicular to ^n
		// Put ^d = ^q - ^p
		// Let lambda be the proportion of ^q from ^a to ^b, i.e. ^q - ^a = lambda * (^b - ^a)
		// Therefore, we have:
		// ^n dot ^d = 0 (by definition)
		// ^n dot (^q - ^p) = 0
		// ^n dot ^q = ^n dot ^p
		// ^n dot (lambda * (^b - ^a) + ^a) = ^n dot ^p
		// lambda * ^n dot (^b - ^a) = ^n dot ^p - ^n dot ^a
		// lambda = (^n dot ^p - ^n dot ^a) / ^n dot (^b - ^a)

		// take the planes of ellipses as horizontal, base plane as altitude = 0 and top plane as altitude = 1
		// lambda is equivalent to the altitude of the position to test
		// so if lambda is not in the closed range [0, 1], the position to test is definitely beyond the frustum

		$n = $this->normal;
		$b = $this->top;
		$a = $this->base;
		$p = $vector;
		$lambda = $n->dot($p->subtract($a)) / $n->dot($b->subtract($a));
		if($lambda < 0 || $lambda > 1){
			return false; // taking
		}
		$q = $a->add($b->subtract($a)->multiply($lambda));
		$d = $q->subtract($p);

		assert($d->dot($n) == 0, "Position-axis difference should be parallel to the ellipses");

		$rightProjection = abs($d->dot($this->rightDir)); // if negative, the point is on the left of the axis, so flip it
		$frontProjection = abs($d->dot($this->frontDir)); // if negative the point is on the back of the axis, so flip it

		$maxRightProjection = $this->baseRightRadius + ($this->topRightRadius - $this->baseRightRadius) * $lambda;
		$maxFrontProjection = $this->baseFrontRadius + ($this->topFrontRadius - $this->baseFrontRadius) * $lambda;

		return $maxRightProjection >= $rightProjection && $maxFrontProjection >= $frontProjection;
	}

	public function estimateSize() : int{
		// A(h) = area of layer h, where h at base = 0 and h at top = 1
		// = pi (baseMinRadius + h (topMinRadius - baseMinRadius)) (baseMaxRadius + h (topMaxRadius - baseMaxRadius))
		// size = (top - base) dot normal * integrate of A(h) on dh from h = 0 to h = 1
		//      = (top - base) dot normal *
		//        baseMinRadius*baseMaxRadius + 1/2 (topMinRadius-baseMinRadius) baseMaxRadius
		//       +1/2 (topMaxRadius-baseMaxRadius) baseMinRadius + 1/3 (topMinRadius-baseMinRadius) (topMaxRadius-baseMaxRadius)
		$height = $this->top->subtract($this->base)->dot($this->normal); // modulus(normal) == 1
		$a = $this->baseRightRadius;
		$b = $this->topRightRadius - $this->baseRightRadius;
		$c = $this->baseFrontRadius;
		$d = $this->topFrontRadius - $this->baseFrontRadius;
		$integrate = $a * $c + ($a * $d + $b * $c) / 2 + $b * $d / 3;
		return round($height * M_PI * $integrate);
	}

	public function getSolidStream() : BlockStream{
		// TODO: Implement getSolidStream() method.
	}

	public function getShallowStream(float $padding, float $margin) : BlockStream{
		// TODO: Implement getShallowStream() method.
	}
}
