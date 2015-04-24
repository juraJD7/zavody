<?php

/**
 * Description of Person
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Person extends Nette\Object {
	
	const TYPE_RUNNER = "Závodník";
	const TYPE_GUIDE = "Rádce";
	const TYPE_ESCORT = "Doprovod";
	
	const ID_MALE = 1;
	const ID_FEMALE = 0;
	const TEXT_MALE = "Muž";
	const TEXT_FEMALE = "Žena";
	
	private $personId;
	private $repository;
	private $systemId;
	private $firstName;
	private $lastName;
	private $nickName;
	private $sex;
	private $birthday;
	private $unit;	
	private $watch;
	
	public function __construct($personId) {
		$this->personId = $personId;
	}
	
	public function getPersonId() {
		return $this->personId;
	}
	
	public function getSystemId() {
		return $this->systemId;
	}
	
	public function setSystemId($systemId) {
		$this->systemId = $systemId;
	}

	public function setRepository($repository) {
		$this->repository = $repository;
	}

	public function getFirstName() {
		return $this->firstName;
	}
	
	public function setFirstName($name) {
		$this->firstName = $name;
	}
	
	public function getLastName() {
		return $this->lastName;
	}
	
	public function setLastName($name) {
		$this->lastName = $name;
	}
	
	public function getNickName() {
		return $this->nickName;
	}
	
	public function setNickName($name) {
		$this->nickName = $name;
	}
	
	public function getSex() {
		switch ($this->sex) {
			case self::ID_FEMALE : return self::TEXT_FEMALE;
			case self::ID_MALE : return self::TEXT_MALE;
			default :
				throw new \Nette\InvalidArgumentException("Neznámý typ pohlaví");
		}
	}
	
	public function getSexId() {
		return $this->sex;
	}

	public function setSex($sex) {
		if (!is_int($sex)) {
			throw new Nette\InvalidArgumentException("Parametr pohlaví musí být číselná konstanta.");
		}
		$this->sex = $sex;
	}
	
	public function getBirthday() {
		return $this->birthday;
	}
	
	public function setBirthday(DateTime $birthday) {
		$this->birthday = $birthday;
	}
	
	public function getUnit() {
		return $this->unit;
	}
	
	public function setUnit(Unit $unit) {
		$this->unit = $unit; 
	}	
	
	public function getWatch() {
		return $this->watch;
	}
	
	public function setWatch(Watch $watch) {
		$this->watch = $watch;
	}
	
	public function getRole($raceId) {
		return $this->repository->getRole($this->systemId, $raceId);
	}
	
	public function getDisplayName() {
		$name = "$this->firstName $this->lastName";
		if (!empty($this->nickName)) {
			$name .= " ($this->nickName)";
		}
		return $name;
	}
}
