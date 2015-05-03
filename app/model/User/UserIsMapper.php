<?php

/**
 * Description of UserIsMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class UserIsMapper extends BaseISMapper {
	
	public function getUser($id) {
		$userIs = $this->skautIS->usr->userDetail(array("ID" => $id));
		$person = $this->skautIS->org->PersonDetail(array("ID" => $userIs->ID_Person));
		$user = new User($id);
		$user->userName = $userIs->UserName;
		$user->personId = $person->ID;
		$user->firstName = $person->FirstName;
		$user->lastName = $person->LastName;
		$user->nickName = $person->NickName;
		return $user;
	}
	
	public function isLoggedIn() {
		return $this->skautIS->getUser()->isLoggedIn();
	}
}
