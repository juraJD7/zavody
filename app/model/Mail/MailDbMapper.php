<?php

/**
 * Description of MailDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class MailDbMapper extends BaseDbMapper {	
	
	public function getAdministratorEmails() {
		$users = $this->database->table('user')->
				where('is_admin', 1);
		$emails = array();
		foreach ($users as $user) {
			if(!is_null($user->email)) {
				$emails[$user->id_user] = $user->email;
			}
		}
		return $emails;
	}
	
	public function getGroupName($groupNumber) {
		return $this->database->table('group')
				->get($groupNumber)
				->name;
	}
	
}
