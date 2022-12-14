<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\entity;

use function max;
use function min;

class Attribute{

	public const ABSORPTION = 0;
	public const SATURATION = 1;
	public const EXHAUSTION = 2;
	public const KNOCKBACK_RESISTANCE = 3;
	public const HEALTH = 4;
	public const MOVEMENT_SPEED = 5;
	public const FOLLOW_RANGE = 6;
	public const HUNGER = 7;
	public const FOOD = 7;
	public const ATTACK_DAMAGE = 8;
	public const EXPERIENCE_LEVEL = 9;
	public const EXPERIENCE = 10;

	/** @var int */
	private $id;
	/** @var float */
	protected $minValue;
	/** @var float */
	protected $maxValue;
	/** @var float */
	protected $defaultValue;
	/** @var float */
	protected $currentValue;
	/** @var string */
	protected $name;
	/** @var bool */
	protected $shouldSend;

	/** @var bool */
	protected $desynchronized = true;

	/** @var Attribute[] */
	protected static $attributes = [];

	public static function init(){
		self::addAttribute(self::ABSORPTION, "minecraft:absorption", 0.00, 340282346638528859811704183484516925440.00, 0.00);
		self::addAttribute(self::SATURATION, "minecraft:player.saturation", 0.00, 20.00, 20.00);
		self::addAttribute(self::EXHAUSTION, "minecraft:player.exhaustion", 0.00, 5.00, 0.0, false);
		self::addAttribute(self::KNOCKBACK_RESISTANCE, "minecraft:knockback_resistance", 0.00, 1.00, 0.00);
		self::addAttribute(self::HEALTH, "minecraft:health", 0.00, 20.00, 20.00);
		self::addAttribute(self::MOVEMENT_SPEED, "minecraft:movement", 0.00, 340282346638528859811704183484516925440.00, 0.10);
		self::addAttribute(self::FOLLOW_RANGE, "minecraft:follow_range", 0.00, 2048.00, 16.00, false);
		self::addAttribute(self::HUNGER, "minecraft:player.hunger", 0.00, 20.00, 20.00);
		self::addAttribute(self::ATTACK_DAMAGE, "minecraft:attack_damage", 0.00, 340282346638528859811704183484516925440.00, 1.00, false);
		self::addAttribute(self::EXPERIENCE_LEVEL, "minecraft:player.level", 0.00, 24791.00, 0.00);
		self::addAttribute(self::EXPERIENCE, "minecraft:player.experience", 0.00, 1.00, 0.00);
		//TODO: minecraft:luck (for fishing?)
		//TODO: minecraft:fall_damage
	}

	/**
	 * @param int    $id
	 * @param string $name
	 * @param float  $minValue
	 * @param float  $maxValue
	 * @param float  $defaultValue
	 * @param bool   $shouldSend
	 *
	 * @return Attribute
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function addAttribute($id, $name, $minValue, $maxValue, $defaultValue, $shouldSend = true){
		if($minValue > $maxValue or $defaultValue > $maxValue or $defaultValue < $minValue){
			throw new \InvalidArgumentException("Invalid ranges: min value: $minValue, max value: $maxValue, $defaultValue: $defaultValue");
		}

		return self::$attributes[(int) $id] = new Attribute($id, $name, $minValue, $maxValue, $defaultValue, $shouldSend);
	}

	/**
	 * @param $id
	 *
	 * @return null|Attribute
	 */
	public static function getAttribute($id){
		return isset(self::$attributes[$id]) ? clone self::$attributes[$id] : null;
	}

	/**
	 * @param $name
	 *
	 * @return null|Attribute
	 */
	public static function getAttributeByName($name){
		foreach(self::$attributes as $a){
			if($a->getName() === $name){
				return clone $a;
			}
		}

		return null;
	}

	/**
	 * Attribute constructor.
	 *
	 * @param      $id
	 * @param      $name
	 * @param      $minValue
	 * @param      $maxValue
	 * @param      $defaultValue
	 * @param bool $shouldSend
	 */
	public function __construct($id, $name, $minValue, $maxValue, $defaultValue, $shouldSend = true){
		$this->id = (int) $id;
		$this->name = (string) $name;
		$this->minValue = (float) $minValue;
		$this->maxValue = (float) $maxValue;
		$this->defaultValue = (float) $defaultValue;
		$this->shouldSend = (bool) $shouldSend;

		$this->currentValue = $this->defaultValue;
	}

	/**
	 * @return float
	 */
	public function getMinValue(){
		return $this->minValue;
	}

	/**
	 * @param $minValue
	 *
	 * @return $this
	 */
	public function setMinValue($minValue){
		if($minValue > ($max = $this->getMaxValue())){
			throw new \InvalidArgumentException("Minimum $minValue is greater than the maximum $max");
		}

		if($this->minValue != $minValue){
			$this->desynchronized = true;
			$this->minValue = $minValue;
		}
		return $this;
	}

	/**
	 * @return float
	 */
	public function getMaxValue(){
		return $this->maxValue;
	}

	/**
	 * @param $maxValue
	 *
	 * @return $this
	 */
	public function setMaxValue($maxValue){
		if($maxValue < ($min = $this->getMinValue())){
			throw new \InvalidArgumentException("Maximum $maxValue is less than the minimum $min");
		}

		if($this->maxValue != $maxValue){
			$this->desynchronized = true;
			$this->maxValue = $maxValue;
		}
		return $this;
	}

	/**
	 * @return float
	 */
	public function getDefaultValue(){
		return $this->defaultValue;
	}

	/**
	 * @param $defaultValue
	 *
	 * @return $this
	 */
	public function setDefaultValue($defaultValue){
		if($defaultValue > $this->getMaxValue() or $defaultValue < $this->getMinValue()){
			throw new \InvalidArgumentException("Default $defaultValue is outside the range " . $this->getMinValue() . " - " . $this->getMaxValue());
		}

		if($this->defaultValue !== $defaultValue){
			$this->desynchronized = true;
			$this->defaultValue = $defaultValue;
		}
		return $this;
	}

	public function resetToDefault(){
		$this->setValue($this->getDefaultValue(), true);
	}

	/**
	 * @return float
	 */
	public function getValue(){
		return $this->currentValue;
	}

	/**
	 * @param      $value
	 * @param bool $fit
	 * @param bool $shouldSend
	 *
	 * @return $this
	 */
	public function setValue($value, bool $fit = false, bool $shouldSend = false){
		if($value > $this->getMaxValue() or $value < $this->getMinValue()){
			if(!$fit){
				throw new \InvalidArgumentException("Value $value is outside the range " . $this->getMinValue() . " - " . $this->getMaxValue());
			}
			$value = min(max($value, $this->getMinValue()), $this->getMaxValue());
		}

		if($this->currentValue != $value){
			$this->desynchronized = true;
			$this->currentValue = $value;
		}elseif($shouldSend){
			$this->desynchronized = true;
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 * @return bool
	 */
	public function isSyncable(){
		return $this->shouldSend;
	}

	/**
	 * @return bool
	 */
	public function isDesynchronized() : bool{
		return $this->shouldSend and $this->desynchronized;
	}

	/**
	 * @param bool $synced
	 */
	public function markSynchronized(bool $synced = true){
		$this->desynchronized = !$synced;
	}
}
