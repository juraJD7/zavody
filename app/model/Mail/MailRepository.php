<?php

/**
 * Description of MailRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class MailRepository {
	
	private $dbMapper;
	
	public function __construct(MailDbMapper $dbMapper) {
		$this->dbMapper = $dbMapper;
	}
	
	public function getAdministratorEmails() {
		return $this->dbMapper->getAdministratorEmails();
	}
	
	public function getGroupName($groupNumber) {
		return $this->dbMapper->getGroupName($groupNumber);
	}
}
