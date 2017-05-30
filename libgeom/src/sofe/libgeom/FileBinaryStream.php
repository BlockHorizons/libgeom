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

namespace sofe\libgeom;

use pocketmine\item\Item;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\UUID;

class FileBinaryStream extends BinaryStream{
	/** @var resource */
	protected $fh;

	public function __construct($fh){
		$this->fh = $fh;
	}

	public function getBuffer(){
		throw new \BadMethodCallException;
	}

	public function reset(){
		fseek($this->fh, 0);
	}

	public function getOffset(){
		return ftell($this->fh);
	}

	public function setOffset($offset){
		fseek($this->fh, $offset);
	}

	public function get($len){
		return fread($this->fh, $len);
	}

	public function put($str){
		fwrite($this->fh, $str);
	}

	public function getBool() : bool{
		return (bool) $this->getByte();
	}

	public function putBool($v){
		$this->putByte((bool) $v);
	}

	public function getLong(){
		return Binary::readLong($this->get(8));
	}

	public function putLong($v){
		fwrite($this->fh, Binary::writeLong($v));
	}

	public function getInt(){
		return Binary::readInt($this->get(4));
	}

	public function putInt($v){
		fwrite($this->fh, Binary::writeInt($v));
	}

	public function getLLong(){
		return Binary::readLLong($this->get(8));
	}

	public function putLLong($v){
		fwrite($this->fh, Binary::writeLLong($v));
	}

	public function getLInt(){
		return Binary::readLInt($this->get(4));
	}

	public function putLInt($v){
		fwrite($this->fh, Binary::writeLInt($v));
	}

	public function getSignedShort(){
		return Binary::readSignedShort($this->get(2));
	}

	public function putShort($v){
		fwrite($this->fh, Binary::writeShort($v));
	}

	public function getShort(){
		return Binary::readShort($this->get(2));
	}

	public function putSignedShort($v){
		fwrite($this->fh, Binary::writeShort($v));
	}

	public function getFloat(int $accuracy = -1){
		return Binary::readFloat($this->get(4), $accuracy);
	}

	public function putFloat($v){
		fwrite($this->fh, Binary::writeFloat($v));
	}

	public function getLShort($signed = true){
		return $signed ? Binary::readSignedLShort($this->get(2)) : Binary::readLShort($this->get(2));
	}

	public function putLShort($v){
		fwrite($this->fh, Binary::writeLShort($v));
	}

	public function getLFloat(int $accuracy = -1){
		return Binary::readLFloat($this->get(4), $accuracy);
	}

	public function putLFloat($v){
		fwrite($this->fh, Binary::writeLFloat($v));
	}

	public function getTriad(){
		return Binary::readTriad($this->get(3));
	}

	public function putTriad($v){
		fwrite($this->fh, Binary::writeTriad($v));
	}

	public function getLTriad(){
		return Binary::readLTriad($this->get(3));
	}

	public function putLTriad($v){
		fwrite($this->fh, Binary::writeLTriad($v));
	}

	public function getByte(){
		return ord(fread($this->fh, 1));
	}

	public function putByte($v){
		fwrite($this->fh, chr($v));
	}

	public function getUUID(){
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

	public function getSlot(){
		$id = $this->getVarInt();

		if($id <= 0){
			return Item::get(0, 0, 0);
		}
		$auxValue = $this->getVarInt();
		$data = $auxValue >> 8;
		$cnt = $auxValue & 0xff;

		$nbtLen = $this->getLShort();
		$nbt = "";

		if($nbtLen > 0){
			$nbt = $this->get($nbtLen);
		}

		return Item::get(
			$id,
			$data,
			$cnt,
			$nbt
		);
	}

	public function putSlot(Item $item){
		if($item->getId() === 0){
			$this->putVarInt(0);
			return;
		}

		$this->putVarInt($item->getId());
		$auxValue = (($item->getDamage() ?? -1) << 8) | $item->getCount();
		$this->putVarInt($auxValue);
		$nbt = $item->getCompoundTag();
		$this->putLShort(strlen($nbt));
		$this->put($nbt);
	}

	public function getString(){
		return $this->get($this->getUnsignedVarInt());
	}

	public function putString($v){
		$this->putUnsignedVarInt(strlen($v));
		$this->put($v);
	}

	public function getUnsignedVarInt(){
		return Binary::readUnsignedVarInt($this);
	}

	public function putUnsignedVarInt($v){
		fwrite($this->fh, Binary::writeUnsignedVarInt($v));
	}

	public function getVarInt(){
		return Binary::readVarInt($this);
	}

	public function putVarInt($v){
		fwrite($this->fh, Binary::writeVarInt($v));
	}

	public function getUnsignedVarLong(){
		return Binary::readUnsignedVarLong($this);
	}

	public function putUnsignedVarLong($v){
		fwrite($this->fh, Binary::writeUnsignedVarLong($v));
	}

	public function getVarLong(){
		return Binary::readVarLong($this);
	}

	public function putVarLong($v){
		fwrite($this->fh, Binary::writeVarLong($v));
	}

	public function feof(){
		return feof($this->fh);
	}
}
