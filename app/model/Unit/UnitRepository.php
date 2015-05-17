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
	
	/**
	 * Vrátí jednotku
	 * 
	 * Pokud je uživatel přihlášen, pokusí se načíst jednotku z ISu. Pokud selže,
	 * nebo pokud není uživatel přihlášen, zkusí načíst data z databáze.
	 * 
	 * @param int $id
	 * @return Unit
	 * @throws Nette\Security\AuthenticationException
	 */
	public function getUnit($id) {		
		if ($this->isMapper->isLoggedIn()) {
			try {
				$unit = $this->isMapper->getUnit($id);				
			} catch (Skautis\Wsdl\PermissionException $ex) {
				//pokud nelze načíst data z ISu zkusí se načíst z databáze
				$unit = $this->dbMapper->getUnit($id);
			}
		} else {
			try {
				$unit = $this->dbMapper->getUnit($id);
			} catch (Race\DbNotStoredException $ex) {
				//bez přihlášení nejsou data dostupná, uživatel se musí přihlásit.
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
	 * Vrátí jednotky daného typu
	 * 
	 * @param array $type Typ jednotek, které se mají zobrazit; prázdné pole zobrazí všechny
	 * @param int $parentUnit ID nadřízené jednotky, null použije jako default ID jednotky přihlášeného uživatele
	 * $return Unit[]
	 */
	public function getUnits($type = array(), $parentUnit = NULL) {
		return $this->isMapper->getUnits($this, $type, $parentUnit);
	}
	
	public function save(Unit $unit) {
		return $this->dbMapper->save($unit);
	}
	
	/**
	 * Vrátí nadřízenou jednotku
	 * 
	 * @param int $unitId
	 * @return Unit
	 */
	public function getUnitParent($unitId) {
		$idParent = $this->isMapper->getUnitParentId($unitId);
		return $this->getUnit($idParent);
	}
	
}
