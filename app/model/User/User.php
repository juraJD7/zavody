<?php

/**
 * Description of User
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class User extends Nette\Object {
	
	const ADMIN = 1;
	const COMMON = 0;
	
	/**
	 *
	 * @var \UserRepository
	 */
	private $repository;
	
	/**
	 * ID_User ze SkautISu
	 * 
	 * @var int 
	 */
	private $id;
	
	/**
	 * 
	 * 
	 * @var string 
	 */
	private $userName;
	
	/**
	 * ID_Person ze SkautISu
	 * 
	 * @var int 
	 */
	private $personId;	
	
	/**
	 * 
	 * 
	 * @var string 
	 */
	private $firstName;
	
	/**
	 * 
	 * 
	 * @var string 
	 */
	private $lastName;
	
	/**
	 * 
	 * 
	 * @var string 
	 */
	private $nickName;
	
	/**
	 * 
	 * 
	 * @var string 
	 */
	private $email;
	
	/**
	 * TRUE pokud je uživatel administrátorem aplikaci
	 * 
	 * @var bool 
	 */
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
	
	public function save() {
		$this->repository->save($this);
	}
	
}
