<?php

/**
 * Description of UserManager
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class UserManager extends BaseManager {
	
	public function load($id) {		
		$row = $this->database->table('user')->get($id);
		if(!$row) {
			if ($this->skautIS->getUser()->isLoggedIn()) {
				$this->save($id);
				$row = $this->database->table('user')->get($id);
			} else {
				throw new Nette\InvalidArgumentException("User s id = $id neexistuje");
			}
		}
		return new User($this, $row);
	}
	
	public function save($id) {
		$user = $this->skautIS->usr->userDetail(array("ID" => $id));
		$person = $this->skautIS->org->PersonDetail(array("ID" => $user->ID_Person));
		$data = array(
			"id_user" => $user->ID,
			"username" => $user->UserName,
			"id_person" => $person->ID,
			"firstname" => $person->FirstName,
			"lastname" => $person->LastName,
			"nickname" => $person->NickName,
			"email" => $person->Email,
			"is_admin" => 0
		);
		$this->database->table('user')->insert($data);		
	}
}
