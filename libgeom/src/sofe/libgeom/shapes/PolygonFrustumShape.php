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
use sofe\libgeom\io\LibgeomDataReader;
use sofe\libgeom\io\LibgeomDataWriter;
use sofe\libgeom\LazyStreamsShape;
use sofe\libgeom\LibgeomMathUtils;
use sofe\libgeom\Shape;
use sofe\libgeom\UnsupportedOperationException;

/**
 * A PolygonFrustumShape refers to a prism, a frustum or a pyramid.
 *
 * A stricter definition is that a PolygonFrustumShape consists of a "base", which is an arbitrary polygon, and a
 * "top", which is a translated and magnified/diminished (in both dimensions) copy of the base plane polygon. The top
 * face can be diminished to scale 0, i.e. a pyramid. The magnification is carried out with respect to a "top anchor",
 * with a counterpart "base anchor" below.
 *
 * During construction, the base polygon is defined by 3 or more coplanar points along with a "base anchor" point. The top
 * polygon is defined by a "top anchor" point, which determines its position, and a "top-base ratio", which determines
 * its size. The line along both anchors does not need to be perpendicular to the base polygon plane.
 */
class PolygonFrustumShape extends LazyStreamsShape{
	/** @var Vector3 (positional) */
	private $baseAnchor, $topAnchor;
	/** @var Vector3[] (positional) */
	private $basePolygon, $topPolygon;
	/** @var Vector3 (relative) */
	private $baseNormal;
	/** @var float topLength/baseLength */
	private $topBaseRatio;

	private $baseAreaCache;
	/** @var bool */
	private $isSelfIntersecting;

	/**
	 * @param Level     $level
	 * @param Vector3   $baseAnchor
	 * @param Vector3[] $basePolygon
	 * @param Vector3   $topAnchor
	 * @param float     $topBaseRatio {top linear size} / {base linear size}, i.e. sqrt({top area} / {base area})
	 */
	public function __construct(Level $level, Vector3 $baseAnchor, array $basePolygon, Vector3 $topAnchor, float $topBaseRatio){
		if(count($basePolygon) < 3){
			throw new \InvalidArgumentCountException("Base polygon must have at least 3 points");
		}
		$this->baseAnchor = $baseAnchor->asVector3();
		$this->topAnchor = $topAnchor->asVector3();
		$this->basePolygon = [];
		$this->topPolygon = [];
		foreach($basePolygon as $basePoint){
			$this->basePolygon[] = $basePoint->asVector3();
			$this->topPolygon[] = $topAnchor->add($basePoint->subtract($baseAnchor)->multiply($topBaseRatio));
		}
		$this->topBaseRatio = $topBaseRatio;
		assert(LibgeomMathUtils::areCoplanar($baseAnchor, ...$basePolygon), "Base anchor and base polygon must be coplanar");
		$this->baseNormal = $basePolygon[2]->subtract($basePolygon[1])->cross($basePolygon[0]->subtract($basePolygon[1]))->normalize();
		$this->isSelfIntersecting = $this->_isSelfIntersecting();
		$this->setLevel($level);
	}

	private function _isSelfIntersecting() : bool{
		$lines = [];
		for($i = 1, $iMax = count($this->basePolygon); $i <= $iMax; ++$i){
			$lines[] = [$this->basePolygon[$i - 1], $this->basePolygon[$i === count($this->basePolygon) ? 0 : $i]];
		}
		$linesCount = count($lines);
		for($i = 0; $i < $linesCount; ++$i){
			for($j = $i + 1; $j < $linesCount; ++$j){
				if(LibgeomMathUtils::doSegmentsIntersect($lines[$i][0], $lines[$i][1], $lines[$j][0], $lines[$j][1])){
					return true;
				}
			}
		}
		return false;
	}

	public function isSelfIntersecting() : bool{
		return $this->isSelfIntersecting;
	}

	public function isInside(Vector3 $point) : bool{
		$lambda = $this->baseNormal->dot($point->subtract($this->baseAnchor)) / $this->baseNormal->dot($this->topAnchor->subtract($this->baseAnchor));
//		$q = $this->baseAnchor->add($this->topAnchor->subtract($this->baseAnchor)->multiply($lambda));
		$polygon = [];
		foreach($this->basePolygon as $i => $basePoint){
			$polygon[] = $basePoint->add($this->topPolygon[$i]->subtract($basePoint)->multiply($lambda));
		}
		$intersects = 0;
		$horiz = new Vector3(1, 0, 0);
		for($i = 1, $iMax = count($polygon); $i <= $iMax; ++$i){
			if(LibgeomMathUtils::doesRayIntersectSegment($point, $horiz, $polygon[$i - 1], $polygon[$i === count($polygon) ? 0 : $i])){
				$intersects ^= 1;
			}
		}
		return ($intersects & 1) !== 0;
	}

	public function marginalDistance(Vector3 $vector) : float{
		assert(!$this->isSelfIntersecting());
		$lambda = $this->baseNormal->dot($vector->subtract($this->baseAnchor)) / $this->baseNormal->dot($this->topAnchor->subtract($this->baseAnchor));
//		$q = $this->baseAnchor->add($this->topAnchor->subtract($this->baseAnchor)->multiply($lambda));
		/** @var Vector3[] $polygon */
		$polygon = [];
		foreach($this->basePolygon as $i => $basePoint){
			$polygon[] = $basePoint->add($this->topPolygon[$i]->subtract($basePoint)->multiply($lambda));
		}
		$signum = $this->isInside($vector) ? 1 : -1;
		$minDistance = PHP_INT_MAX;
		foreach($polygon as $i => $point1){
			$point2 = $polygon[$i + 1] ?? $polygon[0];
			$ap = $vector->subtract($point1);
			$ab = $point2->subtract($point1);
			$distance = $ab->multiply($ap->dot($ab) / $ab->lengthSquared())->subtract($ap)->length();
			if($minDistance > $distance){
				$minDistance = $distance;
			}
		}
		return $minDistance * $signum;
	}

	protected function lazyGetCenter() : Vector3{
		/** @var Vector3 $baseCenter */
		$baseCenter = array_reduce($this->basePolygon, function(Vector3 $carry, Vector3 $item){
			$carry->x += $item->x;
			$carry->y += $item->y;
			$carry->z += $item->z;
			return $carry;
		}, new Vector3);
		$topCenter = $this->topAnchor->add($baseCenter->subtract($this->baseAnchor)->multiply($this->topBaseRatio));
		// Weisstein, Eric W. "Conical Frustum." From MathWorld--A Wolfram Web Resource. http://mathworld.wolfram.com/ConicalFrustum.html
		// Formula for the height of the geometric centroid
		$w2 = $this->topBaseRatio ** 2;
		$x2 = 1;
		$wx = $this->topBaseRatio;
		$coef = ($w2 + $x2 * 3 + $wx * 2) / 4 / ($w2 + $x2 + $wx);
		return $baseCenter->add($topCenter->subtract($baseCenter)->multiply($coef));
	}

	protected function lazyGetMinX() : float{
		return min(array_map(function(Vector3 $vector){
			return $vector->x;
		}, $this->basePolygon, $this->topPolygon));
	}

	protected function lazyGetMinY() : float{
		return min(array_map(function(Vector3 $vector){
			return $vector->y;
		}, $this->basePolygon, $this->topPolygon));
	}

	protected function lazyGetMinZ() : float{
		return min(array_map(function(Vector3 $vector){
			return $vector->z;
		}, $this->basePolygon, $this->topPolygon));
	}

	protected function lazyGetMaxX() : float{
		return max(array_map(function(Vector3 $vector){
			return $vector->x;
		}, $this->basePolygon, $this->topPolygon));
	}

	protected function lazyGetMaxY() : float{
		return max(array_map(function(Vector3 $vector){
			return $vector->y;
		}, $this->basePolygon, $this->topPolygon));
	}

	protected function lazyGetMaxZ() : float{
		return max(array_map(function(Vector3 $vector){
			return $vector->z;
		}, $this->basePolygon, $this->topPolygon));
	}

	protected function lazyGetMaxHollowSize(float $padding, float $margin) : int{
		$dx = $this->getMaxX() - $this->getMinX();
		$dy = $this->getMaxY() - $this->getMinY();
		$dz = $this->getMaxZ() - $this->getMinZ();

		return (int) ceil($this->getEstimatedSize() * ($dx + $margin) * ($dy + $margin) * ($dz + $margin) / $dx / $dy / $dz);
	}

	public function isComplete() : bool{
		return true;
	}

	/** @noinspection PhpInconsistentReturnPointsInspection
	 * @param Vector3 $vector
	 * @param float   $padding
	 * @param float   $margin
	 *
	 * @return \Generator
	 *
	 * @throws UnsupportedOperationException
	 */
	public function getHollowStream(Vector3 $vector, float $padding, float $margin) : \Generator{
		if($this->isSelfIntersecting){
			throw new UnsupportedOperationException("Hollow self-intersecting frustums are not supported");
		}
		return parent::getHollowStream($vector, $padding, $margin);
	}

	protected function estimateSize() : int{
		return (int) ceil(LibgeomMathUtils::evalFrustumVolume($this->getBaseArea(), $this->getTopArea(),
			$this->topAnchor->subtract($this->baseAnchor)->dot($this->baseNormal)));
	}

	public function getEstimatedSurfaceSize(float $padding, float $margin) : int{
		return (int) round(
			LibgeomMathUtils::evalFrustumVolume($this->getBaseArea() + $margin ** 2, $this->getTopArea() + $margin ** 2,
				$this->topAnchor->subtract($this->baseAnchor)->dot($this->baseNormal) + $margin * 2)
			- LibgeomMathUtils::evalFrustumVolume($this->getBaseArea() - $padding ** 2, $this->getTopArea() -$padding ** 2,
				$this->topAnchor->subtract($this->baseAnchor)->dot($this->baseNormal) - $padding * 2)
		); // TODO improve implementation
	}

	public function getBaseArea() : float{
		if(!isset($this->baseAreaCache)){
			if($this->isSelfIntersecting()){
				// TODO Implement
			}else{
				$sum = 0.0;
				for($i = 2, $iMax = count($this->basePolygon); $i < $iMax; ++$i){
					$sum += LibgeomMathUtils::getTriangleArea($this->basePolygon[0], $this->basePolygon[$i - 1], $this->basePolygon[$i]);
				}
				return $sum;
			}
		}
		return $this->baseAreaCache;
	}

	public function getTopArea() : float{
		return $this->baseAreaCache * $this->topBaseRatio ** 2;
	}

	public function getBaseAnchor() : Vector3{
		return $this->baseAnchor;
	}

	public function getTopAnchor() : Vector3{
		return $this->topAnchor;
	}

	public function getBasePolygon() : array{
		return $this->basePolygon;
	}

	public function getTopPolygon() : array{
		return $this->topPolygon;
	}

	public function getBaseNormal() : Vector3{
		return $this->baseNormal;
	}

	public function getTopBaseRatio() : float{
		return $this->topBaseRatio;
	}


	public static function fromBinary(/** @noinspection PhpUnusedParameterInspection */
		Server $server, LibgeomDataReader $stream) : Shape{
		$level = $server->getLevelByName($stream->readString());
		$baseAnchor = new Vector3();
		$stream->readVector3f($baseAnchor->x, $baseAnchor->y, $baseAnchor->z);
		$size = $stream->readShort();
		$basePolygon = [];
		for($i = 0; $i < $size; ++$i){
			$basePolygon[] = $v = new Vector3();
			$stream->readVector3f($v->x, $v->y, $v->z);
		}
		$topAnchor = new Vector3();
		$stream->readVector3f($topAnchor->x, $topAnchor->y, $topAnchor->z);
		$topBaseRatio = $stream->readFloat();
		return new PolygonFrustumShape($level, $baseAnchor, $basePolygon, $topAnchor, $topBaseRatio);
	}

	public function toBinary(LibgeomDataWriter $stream){
		$stream->writeString($this->getLevelName());
		$stream->writeVector3f($this->baseAnchor->x, $this->baseAnchor->y, $this->baseAnchor->z);
		$stream->writeShort(count($this->basePolygon));
		foreach($this->basePolygon as $point){
			$stream->writeVector3f($point->x, $point->y, $point->z);
		}
		$stream->writeVector3f($this->topAnchor->x, $this->topAnchor->y, $this->topAnchor->z);
		$stream->writeFloat($this->topBaseRatio);
	}
}
