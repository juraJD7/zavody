<?php

namespace Nette\Security;

/**
 * Description of LoggedUser
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class LoggedUser extends User {
	
	
	protected $skautIS;
	
	private $userName;
	private $unitRepository;
	
	/**
	 *
	 * @var Unit
	 */
	private $unit;
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param \UnitRepository $unitRepository
	 * @param \Nette\Security\IUserStorage $storage
	 * @param \Nette\Security\IAuthenticator $authenticator
	 * @param \Nette\Security\IAuthorizator $authorizator
	 */
	public function __construct(\Skautis\Skautis $skautIS, \UnitRepository $unitRepository, IUserStorage $storage, IAuthenticator $authenticator = NULL, IAuthorizator $authorizator = NULL) {
		parent::__construct($storage, $authenticator, $authorizator);
		$this->skautIS = $skautIS;
		$this->unitRepository = $unitRepository;
	}
	
	public function getSkautISRole() {
		return $this->skautIS->getUser()->getRoleId();
	}
	
	public function getUnit() {
		if (is_null($this->unit)) {
			$this->unit = $this->unitRepository->getUnit($this->skautIS->getUser()->getUnitId());			
		}
		return $this->unit;
	}


	public function getAllSkautISRoles($activeOnly = true) {
        return $this->skautIS->user->UserRoleAll(array(
			"ID_User" => $this->getUserDetail()->ID,
			"IsActive" => $activeOnly
		));
	}
	
	public function updateSkautISRole($id) {
        $response = $this->skautIS->user->LoginUpdate(array(
			"ID_UserRole" => $id, 
			"ID" => $this->skautIS->getUser()->getLoginId()));
        if ($response) {
            $this->skautIS->getUser()->updateLoginData(NULL, $id, $response->ID_Unit);
        }
    }
	
	public function getUserDetail() {
		return $this->skautIS->user->UserDetail();
	}


	public function getUserName() {
		if (is_null($this->userName)) {
			$this->userName = $this->getUserDetail()->UserName;
		}
		return $this->userName;
	}
	
}
