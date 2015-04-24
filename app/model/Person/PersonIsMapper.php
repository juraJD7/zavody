<?php

/**
 * Description of PersonIsMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PersonIsMapper extends BaseISMapper {
	
	public function getPerson($personId) {		
		$isPerson = $this->skautIS->org->PersonDetail(array("ID" => $personId)); 
		if ($personId) {
			$person = new Person($personId);
			$person->firstName = $isPerson->FirstName;
			$person->lastName = $isPerson->LastName;
			$person->nickName = $isPerson->NickName;
			$person->sex = ($isPerson->ID_Sex == "male") ? Person::ID_MALE : Person::ID_FEMALE;
			$person->birthday = DateTime::createFromFormat('Y-m-d', substr($isPerson->Birthday, 0, 10));				
			return $person;
		} else {
			throw new InvalidArgumentException("Osoba nenalezena nebo nemáte oprávnění");
		}
	}
	
	/**
	 * Vrátí seznam osob v jednotce
	 * 
	 * @param PersonRepository $repository
	 * @param int $idUnit
	 * @return array of Persons
	 */
	public function getPersonsByUnit(PersonRepository $repository, $idUnit) {
		$isMembers = $this->skautIS->org->MembershipAll(array("ID_Unit" => $idUnit));
		$members = array();
		foreach ($isMembers as $isMember) {
			$member = $this->getPerson($isMember->ID_Person);			
			$member->repository = $repository;
			$member->unit = $this->unitRepository->getUnit($isMember->ID_Unit);			
			$members[] = $member;
		}
		return $members;		
	}	
}
