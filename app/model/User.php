<?php

/**
 * Description of User
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class User extends Nette\Object {
	
	private $manager;
	
	private $id; //ID_User from SkautIS
	private $username;
	private $personId; //ID_Person from SkautIS
	private $firstname;
	private $lastname;
	private $nickname;
	private $email;
	private $admin;
	
	public function __construct(\UserManager $manager, $user) {
		$this->manager = $manager;
		$this->id = $user->id_user;
		$this->username = $user->username;
		$this->personId = $user->id_person;
		$this->firstname = $user->firstname;
		$this->lastname = $user->lastname;
		$this->nickname = $user->nickname;
		$this->email = $user->email;
		$this->admin = $user->is_admin;
	}
	
	public function getDisplayName() {
		$string = "$this->firstname $this->lastname";
		if(is_null($this->nickname)) {
			$string .= "($this->nickname)";
		}
		return $string;
	}
	
	public function getId() {
		if(is_int($this->id)) {
			return $this->id;
		}
		return null;
	}
	
	public function getUserName() {
		return $this->username;
	}
	
	public function getPersonId() {
		if(is_int($this->personId)) {
			return $this->personId;
		}
		return null;
	}
	
	public function getFirstname() {
		return $this->firstname;
	}
	
	public function getLastname() {
		return $this->lastname;
	}
	
	public function getNickname() {
		return $this->nickname;
	}
	
	public function getEmail() {
		return $this->email;
	}
	
	public function isAdmin() {
		if ($this->admin) {
			return TRUE;
		}
		return FALSE;
	}
	
}
