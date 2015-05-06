<?php

/**
 * Description of UserRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class UserRepository {
	
	private $isMapper;
	private $dbMapper;
	
	/**
	 * 
	 * @param UserIsMapper $isMapper
	 * @param UserDbMapper $dbMapper
	 */
	public function __construct(UserIsMapper $isMapper, UserDbMapper $dbMapper) {
		$this->isMapper = $isMapper;
		$this->dbMapper = $dbMapper;
	}
	
	public function getUser($id) {		
		if ($this->isMapper->isLoggedIn()) {
			try {
			$user = $this->isMapper->getUser($id);
			$user->admin = $this->dbMapper->isAdmin($user->id);
			$this->dbMapper->saveUser($user);
			} catch (Skautis\Wsdl\PermissionException $ex) {
				$user = $this->dbMapper->getUser($id);
			}
		} else {
			$user = $this->dbMapper->getUser($id);
		}
		if (is_null($user)) {
			throw new \Nette\InvalidArgumentException("Uživatel $id nenalezen");
		}
		$user->repository = $this;
		return $user;
	}
	
	public function save(User $user) {
		$this->dbMapper->saveUser($user);
	}


	public function loadNonAdminUsers() {
		return $this->dbMapper->loadNonAdminUsers($this);
	}
	
	public function getAdmins() {
		return $this->dbMapper->getAdmins($this);
	}
}
