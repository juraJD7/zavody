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
		$watch->town = $row->name;
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
			$rowWatch = $this->saveWatch($watch);
			// ucastnici
			$this->saveMembers($watch, $rowWatch);
		} catch (Exception $ex) {
			throw new DbSaveException("Hlídku se nepodařilo uložit do databáze", $ex);
		}
		return true;
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
		if (isset($watch->systemId)) {			
			$rowWatch = $this->database->table('watch')
				->where('id', $watch->id)
				->update($data);				
		} else {
			$rowWatch = $this->database->table('watch')
				->insert($data);
		}
		// race_watch - smažu staré vazby a vložím nové -> aktualizace
		$this->database->table('race_watch')
				->where('watch_id', $rowWatch->id)
				->delete();
		foreach ($watch->races as $race) {
			$this->database->table('race_watch')
					->insert(array(
						"race_id" => $race->id,
						"watch_id" => $rowWatch->id
					));
		}
		return $rowWatch;
	}
	
	private function saveMembers(Watch $watch, $rowWatch) {
		// tabulka participant zustane, pouze se smazou vazby
		$watchMembers = $this->database->table('participant')
				->where('watch', $rowWatch->id);
		foreach ($watchMembers as $watchMember) {
			$this->database->table('participant_race')
					->where('participant_id', $watchMember->id)
					->delete();
		}
		//vytvoreni noveho participanta, je li treba
		foreach ($watch->members as $participant) {						
			$partRow = $this->database->table('participant')
					->where('person_id', $participant->personId)
					->where('watch', $rowWatch->id)
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
					"watch" => $rowWatch->id
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
	
}
