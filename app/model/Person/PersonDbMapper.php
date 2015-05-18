<?php

/**
 * PersonDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PersonDbMapper extends BaseDbMapper {
	
	/**
	 * Vrátí jméno účastníkovy role
	 * 
	 * @param int $systemId ID účastníka
	 * @param int $raceId ID vztažného závodu
	 * @return steing
	 */
	public function getRole($systemId, $raceId) {
		$result = $this->database->table('participant_race')
				->where('participant_id', $systemId)
				->where('race_id', $raceId)
				->fetch();
		if ($result) {
			return $this->database->table('role')
				->get($result->role)->name;
		} else {
			return null;
		}
	}
	
	/**
	 * Vrátí id účastníkovy role
	 * 
	 * @param int $systemId ID účastníka
	 * @param int $raceId ID vztažného závodu
	 * @return steing
	 */
	public function getRoleId($systemId, $raceId) {
		$result = $this->database->table('participant_race')
				->where('participant_id', $systemId)
				->where('race_id', $raceId)
				->fetch();
		if ($result) {
			return $result->role;
		} else {
			return null;
		}
	}
	
	/**
	 * Vrátí účastníka
	 * 
	 * @param int $id 
	 */
	public function getParticipant($id) {
		$row = $this->database->table('participant')
				->get($id);
		if (!$row) {
			throw new Race\DbNotStoredException("Účastník $id není v databázi");
		}
		$participant = new Person($row->person_id);
		$participant->systemId = $row->id;
		$participant->firstName = $row->firstname;
		$participant->lastName = $row->lastname;
		$participant->nickName = $row->nickname;
		$participant->sex = $row->sex;
		$participant->birthday = $row->birthday;
		$participant->unit = $this->unitRepository->getUnit($row->unit);
		$participant->watch = $row->watch;
		$participant->roles = $this->getParticipantRoles($participant->systemId);
		return $participant;
	}
	
	/**
	 * Vrátí pole rolí účastníka ve všech jeho závodech
	 * 
	 * @param int $participantId ID účastníka
	 * @return int[]
	 */
	public function getParticipantRoles($participantId) {
		$result = $this->database->table('participant_race')
				->where('participant_id', $participantId);
		$roles = array();
		foreach ($result as $row) {
			$roles[$row->race_id] = $row->role_id;
		}
		return $roles;
	}

	/**
	 * 
	 * @param PersonRepository $repository
	 * @param int $watchId
	 * @param int $raceId
	 * @return Person[]
	 */
	public function getPersonsByWatch(PersonRepository $repository, $watchId, $raceId) {		
		//nalezení všech členů hlídky
		$rowsMembers = $this->database->table('participant')
				->where('watch', $watchId);
		$members = array();
		foreach ($rowsMembers as $member) {
			//pokud je uživatel účastníkem závodu
			$query = $this->database->table('participant_race')
					->where('participant_id', $member->id);
			if (!is_null($raceId)) {
				$query = $query->where('race_id', $raceId);
			}
			// přidá se účastník do pole k vrácení
			$row = $query->fetch();
			if ($row) {				
				$person = $this->getParticipant($member->id);
				$person->repository = $repository;				
				$members[] = $person;
			}
		}
		return $members;
	}
	
	/**
	 * Vrátí seznam všech dostupných rolí
	 * 
	 * @return \Nette\Database\Selection
	 */
	public function getRoles() {
		return $this->database->table('role');
	}
	
	/**
	 * Vrátí název role podle jejího ID
	 * 
	 * @param int $id ID role
	 * @return string název role
	 */
	public function getRoleName($id) {
		$row = $this->database->table('role')
				->get($id);
		if ($row) {
			return $row->name;
		}
	}
	
	/**
	 * Vrátí pojmenování pohlaví pro aktuální soutěž
	 * 
	 * @param inr $sexId
	 * @return string
	 * @throws \Nette\InvalidArgumentException
	 */
	public function getSexName($sexId) {
		//zjištění soutěže
		$id = $this->database->table('season')
				->get($this->season)->competition;
		$competition = $this->database->table('competition')
				->get($id);
		//pohlaví na základě ID
		if ($sexId == Person::ID_MALE) {
			return $competition->male;
		} else if ($sexId == Person::ID_FEMALE){
			return $competition->female;
		}
		throw new \Nette\InvalidArgumentException("Neplatný formát pro pohlaví člena.");
	}
}
