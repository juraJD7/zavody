<?php

/**
 * Description of UserDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class UserDbMapper {
	
	/**
	 *
	 * @var \Nette\Database\Context
	 */
	protected $database;
	
	public function __construct(\Nette\Database\Context $database) {		
		$this->database = $database;		
	}
	
	public function getUser($id) {
		$row = $this->database->table('user')->get($id);
		if(!$row) {
			throw new DbNotStoredException("User $id nenalezen.");
		}		
		return $this->loadUserFromActiveRow($row);
	}
	
	public function loadUserFromActiveRow($row) {
		$user = new User($row->id_user);
		$user->userName = $row->username;
		$user->personId = $row->id_person;
		$user->firstName = $row->firstname;
		$user->lastName = $row->lastname;
		$user->nickName = $row->nickname;
		$user->email = $row->email;
		$user->admin = $row->is_admin;
		return $user;
	}


	public function saveUser(User $user) {
		$data = array(
			"id_user" => $user->id,
			"username" => $user->userName,
			"id_person" => $user->personId,
			"firstname" => $user->firstName,
			"lastname" => $user->lastName,
			"nickname" => $user->nickName,
			"email" => $user->email,
			"is_admin" => $user->admin,
		);
		$row = $this->database->table('user')->get($user->id);
		if ($row) {
			$row->update($data);
		} else {
			$this->database->table('user')->insert($data);
		}
		
	}
	
	/**
	 * 
	 * @param int $id
	 */
	public function isAdmin($id) {
		$row = $this->database->table('user')->get($id);
		if (!$row) {
			return FALSE;
		}
		return $row->is_admin;
	}
	
	public function loadNonAdminUsers(UserRepository $repository) {
		$table = $this->database->table('user')
				->where('is_admin', FALSE);
		$users = array();
		foreach ($table as $row) {
			$user = $this->loadUserFromActiveRow($row);
			$user->repository = $repository;
			$users[$row->id_user] = $user;
		}
		return $users;
	}
	
	public function getAdmins(UserRepository $repository) {
		$table = $this->database->table('user')
				->where('is_admin', TRUE);
		$users = array();
		foreach ($table as $row) {
			$user = $this->loadUserFromActiveRow($row);
			$user->repository = $repository;
			$users[$row->id_user] = $user;
		}
		return $users;
	}
}
