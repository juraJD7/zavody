<?php

/**
 * Person
 * 
 * třída pro správu osob a účastníků
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Person extends Nette\Object {
	
	const TYPE_RUNNER = 1;
	const TYPE_GUIDE = 2;
	const TYPE_ESCORT = 3;
	
	const ID_MALE = 1;
	const ID_FEMALE = 0;
	
	/**
	 *
	 * @var int ID osoby z ISu 
	 */
	private $personId;
	
	/**
	 *
	 * @var \PersonRepository
	 */
	private $repository;
	
	/**
	 *
	 * @var int jedinečné ID účasníka
	 */
	private $systemId;
	
	/**
	 *
	 * @var string
	 */
	private $firstName;
	
	/**
	 *
	 * @var string
	 */
	private $lastName;
	
	/**
	 *
	 * @var string
	 */
	private $nickName;
	
	/**
	 *
	 * @var int
	 */
	private $sex;
	
	/**
	 *
	 * @var DateTime
	 */
	private $birthday;
	
	/**
	 *
	 * @var \Unit
	 */
	private $unit;
	
	/**
	 *
	 * @var int ID hlídky
	 */
	private $watch;
	
	/**
	 * Pole rolí (hodnota) v jednotlivých závodech(klíč)
	 * 
	 * @var int[]
	 */
	private $roles = array();
	
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
		return $this->repository->getSexName($this->sex);
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
	
	public function setWatch($watchId) {
		$this->watch = $watchId;
	}
	
	public function getRoleName($raceId) {		
		$roleId = $this->roles[$raceId];
		return $this->repository->getRoleName($roleId);
	}
	
	public function getRoleId($raceId) {		
		return $this->roles[$raceId];
	}
	
	public function getDisplayName() {
		$name = "$this->firstName $this->lastName";
		if (!empty($this->nickName)) {
			$name .= " ($this->nickName)";
		}
		return $name;
	}
	
	public function getRaces() {
		$races = array();
		foreach ($this->roles as $race => $role) {
			$races[] = $race;
		}
		return $races;
	}
	
	public function getRoles() {
		return $this->roles;
	}

	public function addRace($raceId, $roleId) {
		$this->roles[$raceId] = $roleId;
	}
	
	public function setRoles($roles) {
		$this->roles = $roles;
	}
}
