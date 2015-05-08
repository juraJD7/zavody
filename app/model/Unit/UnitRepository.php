<?php

/**
 * Description of UnitRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class UnitRepository {
	
	private $isMapper;
	private $dbMapper;
	
	/**
	 * 
	 * @param UnitISMapper $isMapper
	 * @param UnitDbMapper $dbMapper
	 */
	public function __construct(UnitISMapper $isMapper, UnitDbMapper $dbMapper) {
		$this->isMapper = $isMapper;
		$this->dbMapper = $dbMapper;
	}
	
	public function getUnit($id) {
		if ($this->isMapper->isLoggedIn()) {
			try {
				$unit = $this->isMapper->getUnit($id);
			} catch (Skautis\Wsdl\PermissionException $ex) {
				$unit = $this->dbMapper->getUnit($id);
			}
		} else {
			try {
				$unit = $this->dbMapper->getUnit($id);
			} catch (DbNotStoredException $ex) {
				throw new Nette\Security\AuthenticationException("Pro zobrazení záznamu se musíte přihlásit");
			}
		}
		$unit->repository = $this;		
		return $unit;
	}
	
	public function getSubordinateUnits($id) {
		return $this->isMapper->getSubordinateUnits($this, $id);
	}
	
	public function getEmail($id) {
		return $this->isMapper->getEmail($id);
	}
	
	public function getTelephone($id) {
		return $this->isMapper->getTelephone($id);
	}
	
	/**
	 * 
	 * @param array $type Typ jednotek, které se mají zobrazit, prázdné pole zobrazí všechny
	 * @return int $parentUnit ID nadřízené jednotky, null použije jako default ID jednotky přihlášeného uživatele
	 */
	public function getUnits($type = array(), $parentUnit = NULL) {
		return $this->isMapper->getUnits($this, $type, $parentUnit);
	}
	
	public function save(Unit $unit) {
		return $this->dbMapper->save($unit);
	}
	
}
