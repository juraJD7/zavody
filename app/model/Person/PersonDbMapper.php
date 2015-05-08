<?php

/**
 * Description of PersonDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PersonDbMapper extends BaseDbMapper {
	
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
	 * 
	 * @param int $id 
	 */
	public function getParticipant($id) {
		$row = $this->database->table('participant')
				->get($id);
		if (!$row) {
			throw new DbNotStoredException("Účastník $id není v databázi");
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
	 * @return array Person
	 */
	public function getPersonsByWatch(PersonRepository $repository, $watchId, $raceId) {
		$rowsMembers = $this->database->table('participant')
				->where('watch', $watchId);
		$members = array();
		foreach ($rowsMembers as $member) {
			$row = $this->database->table('participant_race')
					->where('participant_id', $member->id);
			if (!is_null($raceId)) {
				$row = $row->where('race_id', $raceId);
			}			
			if ($row) {
				$person = $this->getParticipant($member->id);
				$person->repository = $repository;				
				$members[] = $person;
			}
		}
		return $members;
	}
	
	public function getRoles() {
		return $this->database->table('role');
	}
	
	public function getRoleName($id) {
		$row = $this->database->table('role')
				->get($id);
		if ($row) {
			return $row->name;
		}
	}
	
	public function getSexName($sexId) {
		$id = $this->database->table('competition')
				->get($this->season)->id;
		$competition = $this->database->table('competition')
				->get($id);
		if ($sexId == Person::ID_MALE) {
			return $competition->male;
		} else if ($sexId == Person::ID_FEMALE){
			return $competition->female;
		}
		throw new \Nette\InvalidArgumentException("Neplatný formát pro pohlaví člena.");
	}
}
