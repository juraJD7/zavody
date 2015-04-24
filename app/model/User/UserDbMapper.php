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
			throw new DbNotStoredException("User $id není uložen v databázi");
		}
		$user = new User($id);
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
}
