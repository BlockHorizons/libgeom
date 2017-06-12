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

namespace sofe\libgeom\blockop;

use pocketmine\utils\Binary;
use pocketmine\utils\UUID;

class FileBinaryStream{
	/** @var resource */
	protected $fh;

	public function __construct($fh){
		$this->fh = $fh;
	}

	public function reset(){
		fseek($this->fh, 0);
	}

	public function getOffset() : int{
		return ftell($this->fh);
	}

	public function setOffset(int $offset){
		fseek($this->fh, $offset);
	}

	public function get(int $len){
		return fread($this->fh, $len);
	}

	public function put(string $str){
		fwrite($this->fh, $str);
	}

	public function getBool() : bool{
		return (bool) $this->getByte();
	}

	public function putBool(bool $v){
		$this->putByte((int) $v);
	}

	public function getLong() : int{
		return Binary::readLong($this->get(8));
	}

	public function putLong(int $v){
		$this->put(Binary::writeLong($v));
	}

	public function getInt() : int{
		return Binary::readInt($this->get(4));
	}

	public function putInt(int $v){
		$this->put(Binary::writeInt($v));
	}

	public function getLLong() : int{
		return Binary::readLLong($this->get(8));
	}

	public function putLLong(int $v){
		$this->put(Binary::writeLLong($v));
	}

	public function getLInt() : int{
		return Binary::readLInt($this->get(4));
	}

	public function putLInt(int $v){
		$this->put(Binary::writeLInt($v));
	}

	public function getSignedShort() : int{
		return Binary::readSignedShort($this->get(2));
	}

	public function putShort(int $v){
		$this->put(Binary::writeShort($v));
	}

	public function getShort() : int{
		return Binary::readShort($this->get(2));
	}

	public function putSignedShort(int $v){
		$this->put(Binary::writeShort($v));
	}

	public function getFloat() : float{
		return Binary::readFloat($this->get(4));
	}

	public function putFloat(float $v){
		$this->put(Binary::writeFloat($v));
	}

	public function getLShort(bool $signed = true) : int{
		return $signed ? Binary::readSignedLShort($this->get(2)) : Binary::readLShort($this->get(2));
	}

	public function putLShort(int $v){
		$this->put(Binary::writeLShort($v));
	}

	public function getLFloat() : float{
		return Binary::readLFloat($this->get(4));
	}

	public function putLFloat(float $v){
		$this->put(Binary::writeLFloat($v));
	}

	public function getTriad() : int{
		return Binary::readTriad($this->get(3));
	}

	public function putTriad(int $v){
		$this->put(Binary::writeTriad($v));
	}

	public function getLTriad() : int{
		return Binary::readLTriad($this->get(3));
	}

	public function putLTriad(int $v){
		$this->put(Binary::writeLTriad($v));
	}

	public function getByte() : int{
		return ord($this->get(1));
	}

	public function putByte(int $v){
		$this->put(chr($v));
	}

	public function getUUID() : UUID{
		//This is actually two little-endian longs: UUID Most followed by UUID Least
		$part1 = $this->getLInt();
		$part0 = $this->getLInt();
		$part3 = $this->getLInt();
		$part2 = $this->getLInt();
		return new UUID($part0, $part1, $part2, $part3);
	}

	public function putUUID(UUID $uuid){
		$this->putLInt($uuid->getPart(1));
		$this->putLInt($uuid->getPart(0));
		$this->putLInt($uuid->getPart(3));
		$this->putLInt($uuid->getPart(2));
	}

	public function feof() : bool{
		return feof($this->fh);
	}
}
