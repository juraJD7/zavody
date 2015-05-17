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
	
	/**
	 * Vrátí uživatele
	 * 
	 * @param int $id ID uživatele
	 * @return User
	 * @throws Race\DbNotStoredException
	 */
	public function getUser($id) {
		$row = $this->database->table('user')->get($id);
		if(!$row) {
			throw new Race\DbNotStoredException("User $id nenalezen.");
		}		
		return $this->loadUserFromActiveRow($row);
	}
	
	/**
	 * Vytvoří uživatele z řádku databáze
	 * 
	 * @param Nette\Database\ActiveRow $row
	 * @return \User
	 */
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

	/**
	 * Uloží uživatele do databáze
	 * 
	 * V případě existujícího záznamu pouze aktualizuje data
	 * 
	 * @param User $user
	 */
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
	 * Vrátí, jestli je uživatel adminstrátorem
	 * 
	 * @param int $id ID uživatele
	 */
	public function isAdmin($id) {
		$row = $this->database->table('user')->get($id);
		if (!$row) {
			return FALSE;
		}
		return $row->is_admin;
	}
	
	/**
	 * Vrátí všechny uživatele v databázi, kteří nejsou administrátory
	 * 
	 * @param UserRepository $repository
	 * @return User[]
	 */
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
	
	/**
	 * Vrátí všechny adminstrátory aplikace
	 * 
	 * @param UserRepository $repository
	 * @return User[]
	 */
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
