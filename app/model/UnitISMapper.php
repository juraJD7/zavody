<?php

/**
 * Description of UnitISMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class UnitISMapper extends BaseISMapper {
	
	
	/**
	 * @return Unit
	 */
	public function getUnit($id) {
		$result = $this->skautIS->org->UnitDetail(array("ID" => $id));			
		return $this->getUnitFromStdClass($result);
	}
	
	/**
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
	 * @return Unit[] pole přímých podřízených jednotek
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
	
	public function getEmail($id) {
		$email = $this->skautIS->org->UnitContactAll(array(
			"ID_Unit" => $id,
			"ID_ContactType" => "email_hlavni"
		));
		if(is_array($email)) {										
			return $email{0}->Value;	
		}
	}
	
	public function getTelephone($id) {
		$telephone = $this->skautIS->org->UnitContactAll(array(
			"ID_Unit" => $id,
			"ID_ContactType" => "telefon_hlavni"
		));
		if(is_array($telephone)) {										
			return $telephone{0}->Value;	
		}
	}
	
}
