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

namespace sofe\libgeom\shapes;

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;
use sofe\libgeom\LazyStreamsShape;
use sofe\libgeom\LibgeomBinaryStream;
use sofe\libgeom\Shape;

/**
 * A CircularFrustumShape refers to a conical frustum, a cylinder or a cone.
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
 * top, it can be a point (a circle with zero radius) or an ellipse (or a circle). If it is an ellipse, <b>The minor
 * radius of the top must be parallel to either the major or minor radius of the bottom</b>. The ratio of
 * minor and major radii of the top ellipse may or may not be equal or reciprocal to that of the bottom ellipse.
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
class CircularFrustumShape extends LazyStreamsShape{
	/** @var Vector3 */
	private $base;
	/** @var Vector3|null */
	private $top;
	/** @var float|null */
	private $baseRightRadius, $baseFrontRadius, $topRightRadius, $topFrontRadius;
	/** @var Vector3|null */
	private $normal, $rightDir, $frontDir;

	/**
	 * @param Level   $level           the level that the shape is located in.
	 * @param Vector3 $base            the position vector representing the midpoint of the base ellipse
	 * @param Vector3 $top             the position vector representing the midpoint of the top ellipse
	 * @param Vector3 $normal          the relative vector representing the orientation of line perpendicular to the plane of both ellipses, will be automatically normalized
	 * @param Vector3 $baseRightCircum the position vector representing the intersection of the right radius and the circumference of the base ellipse
	 * @param float   $baseFrontRadius the front-back radius of the base ellipse, must be positive, may be smaller than |$baseRightCircum-$base|
	 * @param float   $topRightRadius  the left-right radius of the top ellipse, must be non-negative
	 * @param float   $topFrontRadius  the front-back radius of the top ellipse, may be smaller than $topRightRadius but parallel to $baseFrontRadius, must be non-negative, must be zero if and only if $topRightRadius is zero
	 */
	public function __construct(Level $level, Vector3 $base, Vector3 $top = null, Vector3 $normal = null, Vector3 $baseRightCircum = null, float $baseFrontRadius = null, float $topRightRadius = null, float $topFrontRadius = null){
		$this->setLevel($level);
		assert($normal === null or abs($normal->dot($base->subtract($baseRightCircum))) < 1e-10, "baseRightCircum-base is not perpendicular to the normal");
		$this->base = $base !== null ? $base->asVector3() : null;
		$this->top = $top !== null ? $base->asVector3() : null;
		if($normal !== null){
			$this->normal = $normal->normalize();
			if($baseRightCircum !== null){
				$baseRightRadiusLine = $baseRightCircum->subtract($this->base);
				$this->rightDir = $baseRightRadiusLine->normalize();
				$this->baseRightRadius = $baseRightRadiusLine->length();
				assert($this->baseRightRadius > 0);
				assert($this->baseFrontRadius > 0);
				if($topRightRadius !== null and $topFrontRadius !== null){
					assert(($topRightRadius <=> 0) === ($topFrontRadius <=> 0) and $topRightRadius >= 0);
				}
				$this->frontDir = $this->normal->cross($this->rightDir);
			}
		}
		$this->baseFrontRadius = $baseFrontRadius;
		$this->topRightRadius = $topRightRadius;
		$this->topFrontRadius = $topFrontRadius;
	}


	public function getBase() : Vector3{
		return $this->base;
	}

	public function setBase(Vector3 $base) : CircularFrustumShape{
		$this->base = $base !== null ? $base->asVector3() : null;
		$this->onDimenChanged();
		return $this;
	}

	public function getTop(){
		return $this->top;
	}

	public function setTop(Vector3 $top = null) : CircularFrustumShape{
		$this->top = $top !== null ? $top->asVector3() : null;
		$this->onDimenChanged();
		return $this;
	}

	public function getNormal(){
		return $this->normal;
	}

	public function getRightDir(){
		return $this->rightDir;
	}

	public function getFrontDir(){
		return $this->frontDir;
	}

	public function unsetDirections() : CircularFrustumShape{
		unset($this->normal, $this->rightDir, $this->frontDir);
		$this->onDimenChanged();
		return $this;
	}

	public function setNormal(Vector3 $normal) : CircularFrustumShape{
		if($this->frontDir !== null and $this->rightDir !== null){
			throw new \RuntimeException("Call unsetDirections() before calling setNormal()");
		}
		$this->normal = $normal->normalize();
		if(isset($this->frontDir)){
			$this->rightDir = $this->frontDir->cross($this->normal);
		}elseif(isset($this->rightDir)){
			$this->frontDir = $this->normal->cross($this->rightDir);
		}
		$this->onDimenChanged();
		return $this;
	}

	public function setRightDir(Vector3 $rightDir) : CircularFrustumShape{
		if($this->frontDir !== null and $this->normal !== null){
			throw new \RuntimeException("Call unsetDirections() before calling setRightDir()");
		}
		$this->rightDir = $rightDir;
		if(isset($this->normal)){
			$this->frontDir = $this->normal->cross($this->rightDir);
		}elseif(isset($this->frontDir)){
			$this->normal = $this->rightDir->cross($this->frontDir);
		}
		$this->onDimenChanged();
		return $this;
	}

	public function setFrontDir(Vector3 $frontDir) : CircularFrustumShape{
		if($this->rightDir !== null and $this->normal !== null){
			throw new \RuntimeException("Call unsetDirections() before calling setRightDir()");
		}
		$this->frontDir = $frontDir;

		if(isset($this->rightDir)){
			$this->normal = $this->rightDir->cross($this->frontDir);
		}elseif(isset($this->normal)){
			$this->rightDir = $this->frontDir->cross($this->normal);
		}
		$this->onDimenChanged();
		return $this;
	}

	public function rotate(Vector3 $normal, Vector3 $rightDir) : CircularFrustumShape{
		assert(abs($normal->dot($rightDir)) < 1e-10);
		$this->normal = $normal->normalize();
		$this->rightDir = $rightDir->normalize();
		$this->frontDir = $this->normal->cross($this->rightDir);
		$this->onDimenChanged();
		return $this;
	}

	public function getBaseRightRadius(){
		return $this->baseRightRadius;
	}

	public function setBaseRightRadius(float $radius = null) : CircularFrustumShape{
		$this->baseRightRadius = $radius;
		$this->onDimenChanged();
		return $this;
	}

	public function getBaseFrontRadius(){
		return $this->baseFrontRadius;
	}

	public function setBaseFrontRadius(float $radius = null) : CircularFrustumShape{
		$this->baseFrontRadius = $radius;
		$this->onDimenChanged();
		return $this;
	}

	public function getTopRightRadius(){
		return $this->topRightRadius;
	}

	public function setTopRightRadius(float $radius = null) : CircularFrustumShape{
		$this->topRightRadius = $radius;
		$this->onDimenChanged();
		return $this;
	}

	public function getTopFrontRadius(){
		return $this->topFrontRadius;
	}

	public function setTopFrontRadius(float $radius = null) : CircularFrustumShape{
		$this->topFrontRadius = $radius;
		$this->onDimenChanged();
		return $this;
	}

	public function isComplete() : bool{
		return isset($this->top, $this->normal, $this->baseRightRadius, $this->baseFrontRadius, $this->topRightRadius, $this->topFrontRadius);
	}

	public function isInside(Vector3 $vector) : bool{
		assert($this->isComplete());

		// See draft-1

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

		$rightRadius = $this->baseRightRadius + ($this->topRightRadius - $this->baseRightRadius) * $lambda;
		$frontRadius = $this->baseFrontRadius + ($this->topFrontRadius - $this->baseFrontRadius) * $lambda;

		return ($rightProjection / $rightRadius) ** 2 + ($frontProjection / $frontRadius) ** 2 <= 1;
	}

	public function marginalDistance(Vector3 $vector) : float{
		assert($this->isComplete());

		$n = $this->normal;
		$b = $this->top;
		$a = $this->base;
		$p = $vector;
		$lambda = $n->dot($p->subtract($a)) / $n->dot($b->subtract($a));

		$topDistance = $lambda - 1;
		$baseDistance = 0 - $lambda;
		$vertDistance = abs($topDistance) < abs($baseDistance) ? $topDistance : $baseDistance;

		$q = $a->add($b->subtract($a)->multiply($lambda));
		$D = $p->subtract($q);

		assert($D->dot($n) == 0, "Position-axis difference should be parallel to the ellipses");

		$rightRadius = $this->baseRightRadius + ($this->topRightRadius - $this->baseRightRadius) * $lambda;
		$frontRadius = $this->baseFrontRadius + ($this->topFrontRadius - $this->baseFrontRadius) * $lambda;

		$angle = acos($D->dot($this->rightDir) / $D->length());
		$shouldRadius = $rightRadius * $frontRadius / sqrt(($rightRadius * sin($angle)) ** 2 + ($frontRadius * cos($angle)) ** 2);
		$horizDistance = $D->length() - $shouldRadius;
		// FIXME The shortest distance from the wall of the frustum rather than the horizontal distance should be used. Right now, if the angle between the horizontal and the wall of the frustum deviates a lot from 90 degrees, the wall may become too thin, or even leaving gaps in between.

		return abs($vertDistance) < abs($horizDistance) ? $vertDistance : $horizDistance;
	}

	protected function estimateSize() : int{
		assert($this->isComplete());

		// A(h) = area of layer h, where h at base = 0 and h at top = 1
		// = pi (baseRightRadius + h (topRightRadius - baseRightRadius)) (baseFrontRadius + h (topFrontRadius - baseFrontRadius))
		// size = (top - base) dot normal * integrate of A(h) on dh from h = 0 to h = 1
		//      = (top - base) dot normal *
		//        baseRightRadius*baseFrontRadius + 1/2 (topRightRadius-baseRightRadius) baseFrontRadius
		//       +1/2 (topFrontRadius-baseFrontRadius) baseRightRadius + 1/3 (topRightRadius-baseRightRadius) (topFrontRadius-baseFrontRadius)
		$height = $this->top->subtract($this->base)->dot($this->normal); // modulus(normal) == 1
		$a = $this->baseRightRadius;
		$b = $this->topRightRadius - $this->baseRightRadius;
		$c = $this->baseFrontRadius;
		$d = $this->topFrontRadius - $this->baseFrontRadius;
		$integrate = $a * $c + ($a * $d + $b * $c) / 2 + $b * $d / 3;
		return (int) round($height * M_PI * $integrate);
	}

	protected function lazyGetMinX() : float{
		assert($this->isComplete());
		$base = $this->evalObliqueRadius($this->rightDir->multiply($this->baseRightRadius)->x, $this->frontDir->multiply($this->baseFrontRadius)->x);
		$top = $this->evalObliqueRadius($this->rightDir->multiply($this->topRightRadius)->x, $this->frontDir->multiply($this->topFrontRadius)->x);
		return min($this->base->x - $base, $this->top->x - $top);
	}

	protected function lazyGetMaxX() : float{
		assert($this->isComplete());
		$base = $this->evalObliqueRadius($this->rightDir->multiply($this->baseRightRadius)->x, $this->frontDir->multiply($this->baseFrontRadius)->x);
		$top = $this->evalObliqueRadius($this->rightDir->multiply($this->topRightRadius)->x, $this->frontDir->multiply($this->topFrontRadius)->x);
		return max($this->base->x + $base, $this->top->x + $top);
	}

	protected function lazyGetMinY() : float{
		assert($this->isComplete());
		$base = $this->evalObliqueRadius($this->rightDir->multiply($this->baseRightRadius)->y, $this->frontDir->multiply($this->baseFrontRadius)->y);
		$top = $this->evalObliqueRadius($this->rightDir->multiply($this->topRightRadius)->y, $this->frontDir->multiply($this->topFrontRadius)->y);
		return min($this->base->y - $base, $this->top->y - $top);
	}

	protected function lazyGetMaxY() : float{
		assert($this->isComplete());
		$base = $this->evalObliqueRadius($this->rightDir->multiply($this->baseRightRadius)->y, $this->frontDir->multiply($this->baseFrontRadius)->y);
		$top = $this->evalObliqueRadius($this->rightDir->multiply($this->topRightRadius)->y, $this->frontDir->multiply($this->topFrontRadius)->y);
		return max($this->base->y + $base, $this->top->y + $top);
	}

	protected function lazyGetMinZ() : float{
		assert($this->isComplete());
		$base = $this->evalObliqueRadius($this->rightDir->multiply($this->baseRightRadius)->z, $this->frontDir->multiply($this->baseFrontRadius)->z);
		$top = $this->evalObliqueRadius($this->rightDir->multiply($this->topRightRadius)->z, $this->frontDir->multiply($this->topFrontRadius)->z);
		return min($this->base->z - $base, $this->top->z - $top);
	}

	protected function lazyGetMaxZ() : float{
		assert($this->isComplete());
		$base = $this->evalObliqueRadius($this->rightDir->multiply($this->baseRightRadius)->z, $this->frontDir->multiply($this->baseFrontRadius)->z);
		$top = $this->evalObliqueRadius($this->rightDir->multiply($this->topRightRadius)->z, $this->frontDir->multiply($this->topFrontRadius)->z);
		return max($this->base->z + $base, $this->top->z + $top);
	}

	private function evalObliqueRadius(float $a, float $b) : float{
		// Nominal Animal (https://math.stackexchange.com/users/318422/nominal-animal),
		// Minimum and maximum points of ellipse in 3D,
		// URL (version: 2017-06-04): https://math.stackexchange.com/q/2309239

		return sqrt($a * $a + $b * $b);
	}

	protected function lazyGetMaxShallowSize(float $padding, float $margin) : int{
		assert($this->isComplete());

		$height = $this->top->subtract($this->base)->dot($this->normal); // modulus(normal) == 1
		$height += $margin * 2;
		$a = $this->baseRightRadius + $margin;
		$b = $this->topRightRadius - $this->baseRightRadius;
		$c = $this->baseFrontRadius + $margin;
		$d = $this->topFrontRadius - $this->baseFrontRadius;
		$integrate = $a * $c + ($a * $d + $b * $c) / 2 + $b * $d / 3;
		return (int) ceil($height * M_PI * $integrate * 1.3);
	}


	public static function fromBinary(/** @noinspection PhpUnusedParameterInspection */
		Server $server, LibgeomBinaryStream $stream) : Shape{
		$level = $server->getLevelByName($stream->getString());
		$base = new Vector3();
		$baseRightCircum = new Vector3();
		$top = new Vector3();
		$normal = new Vector3();
		$stream->getVector3f($base->x, $base->y, $base->z);
		$stream->getVector3f($baseRightCircum->x, $baseRightCircum->y, $baseRightCircum->z);
		$baseFrontRadius = $stream->getFloat();
		$stream->getVector3f($top->x, $top->y, $top->z);
		$topRightRadius = $stream->getFloat();
		$topFrontRadius = $stream->getFloat();
		$stream->getVector3f($normal->x, $normal->y, $normal->z);
		return new CircularFrustumShape($level, $base, $top, $normal, $baseRightCircum, $baseFrontRadius, $topRightRadius, $topFrontRadius);
	}

	public function toBinary(LibgeomBinaryStream $stream){
		$stream->putString($this->getLevelName());
		$stream->putVector3f($this->base->x, $this->base->y, $this->base->z);
		$baseRightCircum = $this->base->add($this->rightDir->multiply($this->baseRightRadius));
		$stream->putVector3f($baseRightCircum->x, $baseRightCircum->y, $baseRightCircum->z);
		$stream->putFloat($this->baseFrontRadius);
		$stream->putVector3f($this->top->x, $this->top->y, $this->top->z);
		$stream->putFloat($this->topRightRadius);
		$stream->putFloat($this->topFrontRadius);
		$stream->putVector3f($this->normal->x, $this->normal->y, $this->normal->z);
	}
}
