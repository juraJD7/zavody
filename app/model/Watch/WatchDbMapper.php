<?php

/**
 * Description of WatchDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class WatchDbMapper extends BaseDbMapper {
	
	private $raceRepositoryFactory;
	private $personRepositoryFactory;


	public function __construct(\Nette\Database\Context $database, \UserRepository $userRepository, \UnitRepository $unitRepository, $raceRepositoryFactory, $personRepositoryFactory) {
		parent::__construct($database, $userRepository, $unitRepository);
		$this->raceRepositoryFactory = $raceRepositoryFactory;
		$this->personRepositoryFactory = $personRepositoryFactory;
	}
	
	/**
	 * 
	 * @return RaceRepository
	 */
	private function getRaceRepository() {
		return call_user_func($this->raceRepositoryFactory);
	}
	
	/**
	 * 
	 * @return PersonRepository
	 */
	private function getPersonRepository() {
		return call_user_func($this->personRepositoryFactory);
	}


	public function getWatch($id, WatchRepository $repository) {
		$row = $this->database->table('watch')->get($id);		
		if(!$row) {
			throw new Nette\InvalidArgumentException("Hlídka $id neexistuje");
		}
		return $this->loadFromActiveRow($row, $repository);
	}
	
	public function getWatchs($raceId, WatchRepository $repository) {
		$result = $this->database->table('race_watch')
				->where('race_id', $raceId);
		$watchs = array();
		foreach ($result as $row) {
			$watchs[] = $this->getWatch($row->watch_id, $repository);
		}
		return $watchs;
	}


	/**
	 * 
	 * @param Nette\Database\Table\ActiveRow $row
	 * @return \Watch
	 */
	public function loadFromActiveRow(Nette\Database\Table\ActiveRow $row, WatchRepository $repository) {
		$watch = new Watch($row->id);
		$watch->repository = $repository;
		$watch->name = $row->name;
		$watch->town = $row->town;
		$watch->emailLeader = $row->email_leader;
		$watch->emailGuide = $row->email_guide;
		$watch->category = $row->category;
		$watch->getRaces();
		$watch->getMembers();
		/*$resultMembers = $this->database->table('participant')
				->where('watch', $watch->id);
		foreach ($resultMembers as $row) {
			$member = $this->getPersonRepository()->getParticipant($row->id);
			$watch->addMember($member);
		}	*/	
		return $watch;
	}

	public function getAuthor($id) {
		$authorId = $this->database->table('watch')->get($id)->author;
		return $this->userRepository->getUser($authorId);
	}
	
	public function getUnit($id, $type) {
		$unitId = $this->database->table('watch')->get($id)->$type;
		return $this->unitRepository->getUnit($unitId);
	}
	
	public function getPoints($watchId, $raceId) {
		return $this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('watch_id', $watchId)
				->fetch()
				->points;
	}
	
	public function getAdvance($watchId, $raceId) {
		$advance = $this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('watch_id', $watchId)
				->fetch()
				->advance;
		if($advance) {
			return TRUE;
		}
		return FALSE;
	}
	
	public function getOrder($watchId, $raceId) {
		return $this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('watch_id', $watchId)
				->fetch()
				->order;
	}
	
	public function isConfirmed($watchId, $raceId) {
		$confirmed = $this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('watch_id', $watchId)
				->fetch()
				->confirmed;
		if ($confirmed) {
			return TRUE;
		}
		return FALSE;
	}
	
	public function save(Watch $watch) {
		try {
			$watchId = $this->saveWatch($watch);
			// ucastnici
			$this->saveMembers($watch, $watchId);
		} catch (Exception $ex) {
			throw new DbSaveException("Hlídku se nepodařilo uložit do databáze", $ex);
		}		
		return $watchId;
	}
	
	private function saveWatch(Watch $watch) {
		$data = array(
			"name" =>  $watch->name,
			"author" => $watch->author->id,
			"group" => $watch->group->id,
			"troop" => $watch->troop->id,
			"town" => $watch->town,
			"email_leader" => $watch->emailLeader,
			"email_guide" => $watch->emailGuide
		);			
		if (!is_null($watch->id)) {			
			$rowWatch = $this->database->table('watch')
				->where('id', $watch->id)
				->update($data);
			$watchId = $watch->id;
		} else {
			$rowWatch = $this->database->table('watch')
				->insert($data);
			$watchId = $rowWatch->id;
		}
		
		// race_watch - smažu staré vazby a vložím nové -> aktualizace
		$this->database->table('race_watch')
				->where('watch_id', $watchId)
				->delete();
		foreach ($watch->races as $race) {
			$this->database->table('race_watch')
					->insert(array(
						"race_id" => $race->id,
						"watch_id" => $watchId
					));
		}
		return $watchId;
	}
	
	private function saveMembers(Watch $watch, $watchId) {
		// tabulka participant zustane, pouze se smazou vazby
		$watchMembers = $this->database->table('participant')
				->where('watch', $watchId);
		foreach ($watchMembers as $watchMember) {
			$this->database->table('participant_race')
					->where('participant_id', $watchMember->id)
					->delete();
		}
		//vytvoreni noveho participanta, je li treba
		foreach ($watch->members as $participant) {						
			$partRow = $this->database->table('participant')
					->where('person_id', $participant->personId)
					->where('watch', $watchId)
					->fetch();
			if (!$partRow) {
				$data = array(
					"person_id" => $participant->personId,
					"firstname" => $participant->firstName,
					"lastname" => $participant->lastName,
					"nickname" => $participant->nickName,
					"sex" => $participant->sexId,
					"birthday" => $participant->birthday,
					"unit" => $participant->unit->id,				
					"watch" => $watchId
				);
				$this->unitRepository->save($participant->unit);
				$partRow = $this->database->table('participant')
					->insert($data);
			}				
			foreach ($participant->roles as $race => $role) {
				$this->database->table('participant_race')
					->insert(array(
						"participant_id" => $partRow->id,
						"race_id" => $race,
						"role_id" => $role
					));
			}			
		}
	}
	
	public function getRaces($watchId) {
		$result =  $this->database->table('race_watch')
				->where('watch_id', $watchId);
		$raceIds = array();
		foreach ($result as $row) {
			$raceIds[] = $row->race_id;
		}
		return $raceIds;
	}
	
	public function deleteAllMembers($watchId, $raceId = null) {
		$members = $this->database->table('participant')
				->where('watch', $watchId);
		foreach ($members as $member) {
			$this->database->table('participant_race')
					->where('participant_id', $member->id)
					->where('race_id', $raceId)
					->delete();
		}
		$members->delete();
		return 1;
	}
	
	private function validateAgeMember($personId, $roleId, $race) {
		if ($roleId == Person::TYPE_ESCORT) {			
			return TRUE;
		}		
		$person = $this->getPersonRepository()->getPerson($personId);
		if ($roleId == Person::TYPE_RUNNER ) {			
			$age = $person->birthday > $race->getRunnerAge();
			$message = "Pro osobu $person->displayName byl překročen věk závodníka. Zvolte jinou kategorii.";
		}
		if ($roleId == Person::TYPE_GUIDE ) {			
			$age = $person->birthday > $race->getGuideAge();				
			$message = "Pro osobu $person->displayName byl překročen věk rádce. Starší osoby se musí uvést jako doprovod.";
		}
		
		if ($age) {
			return $age;
		} else {
			return $message;
		}
	}
	
	private function validateMemberCollision($personId, $roleId, $race, $watchId) {
		$collisionRoles = array(\Person::TYPE_RUNNER, \Person::TYPE_GUIDE);
		if (!in_array($roleId, $collisionRoles)) {
			return TRUE;
		}
		
		$participants = $this->database->table('participant')
				->where('person_id', $personId);
		foreach ($participants as $participant) {
			$races = $this->database->table('participant_race')
					->where('participant_id', $participant->id)
					->where('role_id', $collisionRoles);
			foreach ($races as $raceId) {				
				$race = $this->database->table('race')->get($raceId->race_id);				
				if ($race->season == $race->season) {
					$person = $this->getPersonRepository()->getPerson($personId);
					if (is_null($watchId)) {						
						return "Osoba $person->displayName je již členem jiné hlídky";
					} else {
						if ($watchId != $participant->watch) {							
							return "Osoba $person->displayName je již členem jiné hlídky";
						}
					}				
				}
			}
		}
		return TRUE;
	}

	public function validateMember($personId, $roleId, $race, $watchId = NULL) {
		
		$validateAge = $this->validateAgeMember($personId, $roleId, $race);			
		if ($validateAge !== TRUE) {			
			return $validateAge;
		}				
		
		return $this->validateMemberCollision($personId, $roleId, $race, $watchId);		
		
	}
	
	public function fixCategory($watchId, $category) {
		$this->database->table('watch')
			->where('id', $watchId)
			->update(array("category" => $category));
	}
	
	public function processAdvance(Watch $watch, Race $race) {
		
		$advanceRace = $race->getAdvance();
		if ($advanceRace) {			
			$rows = $this->database->table('participant_race')
					->where('race_id', $race->id);
			foreach ($rows as $row) {
				$participant = $this->database->table('participant')
						->where('id', $row->participant_id)
						->where('watch', $watch->id)->fetch();				
				if ($participant) {
					$this->database->table('participant_race')
							->insert(array(
								"participant_id" => $row->participant_id,
								"race_id" => $advanceRace->id,
								"role_id" => $row->role_id
							));
				}
			}
			$this->database->table('race_watch')
					->insert(array(
						"race_id" => $advanceRace->id,
						"watch_id" => $watch->id
					));
		}
	}
	
	public function getToken($watchId, $raceId) {
		return $this->database->table('race_watch')
				->where('watch_id', $watchId)
				->where('race_id', $raceId)
				->fetch()
				->token;
	}
	
	public function setToken($watchId, $raceId, $token) {
		$this->database->table('race_watch')
				->where('watch_id', $watchId)
				->where('race_id', $raceId)
				->update(array("token" => $token));
	}
	
	public function confirm($watchId, $token) {
		return $this->database->table('race_watch')
				->where('watch_id', $watchId)
				->where('token', $token)
				->update(array(
					"confirmed" => 1
				));
	}
}
