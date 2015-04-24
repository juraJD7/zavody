<?php

/**
 * Description of User
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class User extends Nette\Object {
	
	/**
	 *
	 * @var \UserRepository
	 */
	private $repository;
	
	private $id; //ID_User from SkautIS
	private $userName;
	private $personId; //ID_Person from SkautIS
	private $firstName;
	private $lastName;
	private $nickName;
	private $email;
	private $admin;
	
	public function __construct($id) {
		$this->id = $id;
	}	
	
	public function getId() {
		return $this->id;
	}
	
	public function setRepository(UserRepository $repository) {
		$this->repository = $repository;
	}

	public function getUserName() {
		return $this->userName;
	}
	
	public function setUserName($name) {
		$this->userName = $name;
	}

	public function getPersonId() {
		return $this->personId;		
	}
	
	public function setPersonId($personId) {
		$this->personId = $personId;
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
		$this->NickName = $name;
	}
	
	public function getEmail() {
		return $this->email;
	}
	
	public function setEmail($email) {
		$this->email = $email;
	}

	public function isAdmin() {
		return $this->admin;
	}
	
	public function setAdmin($admin) {
		if ($admin) {
			$this->admin = TRUE;
		} else {
			$this->admin = FALSE;
		}
	}

	public function getPersonName() {
		$name = "$this->firstName $this->lastName";
		if (!is_null($this->nickName)) {
			$name .= $this->nickName;
		}
		return $name;
	}

	public function getDisplayName() {
		return "$this->userName (" . $this->getPersonName() . ")";
	}
	
}
