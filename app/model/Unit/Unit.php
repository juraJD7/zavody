<?php

/**
 * Description of Unit
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Unit extends \Nette\Object {
		
	/**
	 * 
	 * @var \UnitRepository
	 */
	private $repository;
	
	private $id;
	private $displayName;
	private $registrationNumber;	
	private $unitType;
	
	private $unitParent;		
	private $email;
	private $telephone;
	private $subordinateUnits = array();
	
	public function __construct($id) {
		if(!is_int($id)) {
			throw new \Nette\InvalidArgumentException("Parametr id musí být integer.");
		}
		$this->id = $id;		
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setRepository(\UnitRepository $repository) {
		$this->repository = $repository;
	}

	public function getDisplayName() {
		return $this->displayName;
	}
	
	public function setDisplayName($displayName) {
		$this->displayName = $displayName;
	}
	
	public function getRegistrationNumber() {
		return $this->registrationNumber;
	}
	
	public function setRegistrationNumber($registrationNumber) {		
		$this->registrationNumber = $registrationNumber;
	}
	
	public function getUnitType() {
		return $this->unitType;
	}
	
	public function setUnitType($unitType) {		
		$this->unitType = $unitType;
	}
	
	public function getUnitParent() {
		if (is_null($this->unitParent)) {
			$this->unitParent = $this->repository->getUnitParent($this->id);
		}
		return $this->unitParent;
	}
	
	public function getTelephone() {		
		return $this->telephone;
	}
	
	public function setTelephone($telephone) {
		$this->telephone = $telephone;
	}

	public function getEmail() {		
		return $this->email;
	}
	
	public function setEmail($email) {
		$this->email = $email;
	}
	
	public function getSubordinateUnits() {
		if (empty($this->subordinateUnits)) {			
			$this->subordinateUnits = $this->repository->getSubordinateUnits($this->id);
		}
		return $this->subordinateUnits;
	}
	
	public function save() {
		$this->repository->save($this);
	}
	
}
