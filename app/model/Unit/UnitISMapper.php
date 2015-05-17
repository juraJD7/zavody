<?php

/**
 * Description of UnitISMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class UnitISMapper {
	
	/**
	 *
	 * @var \Skautis\Skautis 
	 */
	protected $skautIS;	
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 */
	public function __construct(\Skautis\Skautis $skautIS) {
		$this->skautIS = $skautIS;
	}
	
	/**
	 * Vrátí jednotku ze skautISu
	 * 
	 * @param int $id
	 * @return Unit
	 */
	public function getUnit($id) {
		$result = $this->skautIS->org->UnitDetail(array("ID" => $id));
		$unit = $this->getUnitFromStdClass($result);
		try {
			$unit->telephone = $this->getTelephone($id);
			$unit->email = $this->getEmail($id);
		} catch (Exception $ex) {

		}
		return $unit;
	}
	
	/**
	 * 
	 * @param StdClass $stdClass Převede StdClass na Unit a vrátí ji
	 * @return Unit Načte do jednotky základní data ze SkautISu
	 */
	public function getUnitFromStdClass(stdClass $stdClass) {
		$unit = new Unit($stdClass->ID);
		$unit->displayName = $stdClass->DisplayName;
		$unit->registrationNumber = (int) $stdClass->RegistrationNumber;
		$unit->unitType = $stdClass->ID_UnitType;
		return $unit;
	}


	/**
	 * Vrátí pole přímých podřízených jednotek
	 * 
	 * @return Unit[] Pole přímých podřízených jednotek
	 */
	public function getSubordinateUnits(UnitRepository $repository, $idUnitParent) {		
		$result = $this->skautIS->org->UnitAll(array("ID_UnitParent" => $idUnitParent));
		$units = array();
		foreach ($result as $child) {
			$tmp = $this->getUnitFromStdClass($child);
			$tmp->repository = $repository;
			$units[] = $tmp;
			}
		return $units;
	}
	
	/**
	 * Vrátí hlavní email jednotky
	 * 
	 * @param int $id ID jednotky
	 * @return string
	 */
	public function getEmail($id) {
		$email = $this->skautIS->org->UnitContactAll(array(
			"ID_Unit" => $id,
			"ID_ContactType" => "email_hlavni"
		));
		if(is_array($email)) {										
			return $email{0}->Value;	
		}
	}
	
	/**
	 * Vrátí hlavní telefon na jednotku
	 * 
	 * @param int $id ID jednotky
	 * @return string
	 */
	public function getTelephone($id) {
		$telephone = $this->skautIS->org->UnitContactAll(array(
			"ID_Unit" => $id,
			"ID_ContactType" => "telefon_hlavni"
		));
		if(is_array($telephone)) {										
			return $telephone{0}->Value;	
		}
	}
	
	/**
	 * Vrátí všechny podřízené jednotky rodičovské jednotky zadaného typu
	 * 
	 * V případě, že není zadána nadřízená jednotka, použije se podle aktuální role v ISu
	 * 
	 * @param UnitRepository $repository
	 * @param string $type Typ jednotky
	 * @param int $parent ID nadřízené jednotky
	 * @return Unit[]
	 */
	public function getUnits(UnitRepository $repository, $type, $parent) {
		if (is_null($parent)) {
			$parent = $this->skautIS->getUser()->getUnitId();
		}
		//získání jednotek ze skautISu ber rozdílu dat. typu
		$units = $this->skautIS->org->UnitAllUnit(array("ID_Unit" => $parent));		
		$unitType = array();
		foreach ($units as $unit) {
			//výběr jednotek, které jsou jednoho ze zadaných typů
			if(in_array($unit->ID_UnitType, $type) || is_null($type)) {
				$tmp = $this->getUnit($unit->ID);
				$tmp->repository = $repository;
				$unitType[$unit->ID] = $tmp;
			}
		}		
		return $unitType;	
	}
	
	/**
	 * 
	 * @return boool TRUE, pokud je přihlášen, FALSE jinak
	 */
	public function isLoggedIn() {
		return $this->skautIS->getUser()->isLoggedIn();
	}
	
	/**
	 * Vrátí ID nadřízené jednotky
	 * 
	 * @param int $unitId ID jednotky
	 * @return int ID nadřízené jednotky
	 */
	public function getUnitParentId($unitId) {
		$isUnit = $this->skautIS->org->UnitDetail(array("ID" => $unitId));
		return $isUnit->ID_UnitParent;		
	}
	
}
