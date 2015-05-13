<?php

/**
 * Description of RaceDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class RaceDbMapper extends BaseDbMapper {
	
	
	private $watchRepositoryFactory;
	
	/**
	 * 
	 * @param \Nette\Database\Context $database
	 * @param \UserRepository $userRepository
	 * @param \UnitRepository $unitRepository
	 * @param $watchRepositoryFactory
	 */
	public function __construct(\Nette\Database\Context $database, \UserRepository $userRepository, \UnitRepository $unitRepository, $watchRepositoryFactory) {
		parent::__construct($database, $userRepository, $unitRepository);
		$this->watchRepositoryFactory = $watchRepositoryFactory;
	}
	
	/**
	 * 
	 * @return WatchRepository
	 */
	private function getWatchRepository() {
		return call_user_func($this->watchRepositoryFactory);
	}

	/**
	 * 
	 * @param int $id
	 * @return Race
	 */
	public function getRace($id) {
		$row = $this->database->table('race')->get($id);		
		if(!$row) {
			throw new Race\DbNotStoredException("Závod $id neexistuje");
		}
		return $this->loadFromActiveRow($row);
	}
	
	public function getNumRaces() {
		$regions = $this->database->table('region');
		$numRaces = array();
		foreach ($regions as $region) {
			$numRaces[$region->id] = $this->database->table('race')
					->where('region', $region->id)
					->where('season', $this->season)
					->where('round !=', 'C')
					->count();
		}
		return $numRaces;
	}


	/**
	 * 
	 * @param Nette\Database\Table\ActiveRow $row
	 * @return \Race
	 */
	private function loadFromActiveRow(Nette\Database\Table\ActiveRow $row) {
		$race = new Race($row->id);
		$race->name = $row->name;		
		$race->description = $row->description;
		$race->season = $row->season;			
		$race->date = $row->date;
		$race->place = $row->place;
		$race->commander = $row->commander;
		$race->referee = $row->referee;
		$race->telephone = $row->telephone;
		$race->email = $row->email;
		$race->web = $row->web;
		$race->capacity = $row->capacity;
		$race->applicationDeadline = $row->application_deadline;
		$race->targetGroup = $row->target_group;
		$race->commanderEmail = $row->commander_email;
		$race->refereeEmail = $row->referee_email;
		return $race;
	}
	
	/**
	 * 
	 * @param int $season
	 * @return Race
	 */
	public function getStatewideRound($repository, $season) {
		$row = $this->database->table('race')
				->where('season', $season)
				->where('round', 'C')
				->fetch();		
		if($row) {
			$race = $this->loadFromActiveRow($row);
			$race->repository = $repository;
			return $race;
		}
		return null;
	}
	
	/**
	 * 
	 * @return Nette\Database\Selection
	 */
	public function getRegions() {
		return $this->database->table('region');
	}
	
	/**
	 * Vrátí všechny závody v dané sezoně, i již proběhlé
	 * 
	 * @param \RaceRepository $repository
	 * @param int $season
	 * @return Race[]
	 */
	public function getRaces($repository, $season) {
		$result = $this->database->table('race')
				->where('season', $season)
				->order('round, date');
		$races = array();
		foreach ($result as $row) {				
			$race = $this->loadFromActiveRow($row);
			$race->repository = $repository;
			$races[$row->id] = $race;
		}
		return $races;
	}
	
	public function getRacesByWatch(RaceRepository $repository, $watchId) {
		$result = $this->database->table('race_watch')
				->where('watch_id', $watchId);
		$races = array();
		foreach ($result as $row) {
			$raceRow = $this->database->table('race')
					->get($row->race_id);
			$race = $this->loadFromActiveRow($raceRow);
			$race->repository = $repository;
			$races[$row->id] = $race;
		}
		return $races;
	}
	
	/**
	 * 
	 * @param RaceRepository $repository
	 * @param int $season
	 * @return Race[]
	 */
	public function getRacesToLogIn(RaceRepository $repository, $season) {
		$result = $this->database->table('race')
				->where('season', $season);
		$races = array();
		foreach ($result as $row) {			
			$race = $this->loadFromActiveRow($row);
			$race->repository = $repository;
			if ($race->isLoginActive($season)) {				
				$races[$row->id] = $race;
			}
		}
		return $races;
	}

	/**
	 * 
	 * @param int $id
	 * @return \Nette\Database\ActiveRow
	 */
	public function getRound($id) {
		$round = $this->database->table('race')->get($id)->round;
		return $this->database->table('round')->where('short', $round)->fetch();
	} 
	
	/**
	 * 
	 * @param int $seasonId
	 * @return \Nette\Database\ActiveRow
	 */
	public function getSeasonName($seasonId) {
		$season = $this->database->table('season')->get($seasonId);
		$competition = $this->database->table('competition')->get($season->competition)->short;
		return "$competition $season->year";
	}
	
	public function getRegion($id) {
		$region = $this->database->table('race')->get($id)->region;		
		return $this->database->table('region')->get($region);
	}
	
	public function getMembersRange($id) {
		$range = $this->database->table('race')->get($id)->members_range;
		return $this->database->table('members_range')->get($range);
	}
	
	/**
	 * 
	 * @param int $id ID race
	 * @return array of \User
	 */
	public function getEditors($id) {
		$result = $this->database->table('editor_race')->where('race_id', $id);
		$editors = array();
		foreach ($result as $row) {
			$editors[] = $this->userRepository->getUser($row->user_id);
		}
		return $editors;
	}
	
	public function getAuthor($id) {
		$userId = $this->database->table('race')->get($id)->author;
		return $this->userRepository->getUser($userId);
	}

	/**
	 * 
	 * @param int id zavodu
	 * @return int id postupového závodu
	 */
	public function getAdvance($id) {		
		return $this->database->table('race')->get($id)->advance;		
	}
	
	/**
	 * 
	 * @param int $raceId
	 * @return \Nette\Database\ActiveRow
	 */
	public function getKey($raceId) {
		$keyId = $this->database->table('race')->get($raceId)->key;
		return $this->database->table('advance_key')->get($keyId);
	}

	/**
	 * Vrátí parametry závodu ve formě, která lze načíst jako default values pro formulář
	 * 
	 * @param int $id id závodu
	 * @return array 
	 */
	public function getDataForForm($id) {
		$raceArray = $this->database->table('race')->get($id)->toArray();	
		$raceArray['date'] = Date('Y-m-d', $raceArray['date']->getTimestamp());
		$raceArray['application_deadline'] = Date('Y-m-d', $raceArray['application_deadline']->getTimestamp());
		return $raceArray;		
	}
	
	/**
	 * Vrátí pořádající jednotku závodu
	 * 
	 * @param int $id ID závodu
	 * @return Unit
	 */
	public function getOrganizer($id) {
		$unitId = $this->database->table('race')->get($id)->organizer;
		return $this->unitRepository->getUnit($unitId);
	}
	
	public function getGuideAge($season) {
		return $this->database->table('season')
				->get($season)
				->guide_age;
	}
	
	public function getRunnerAge($season) {
		return $this->database->table('season')
				->get($season)
				->runner_age;
	}
	
	public function getMinRunner($membersRange) {
		return $this->database->table('members_range')
				->get($membersRange)
				->min;
	}
	
	public function getMaxRunner($membersRange) {
		return $this->database->table('members_range')
				->get($membersRange)
				->max;
	}
	
	public function getNumWatchs($raceId, $category) {
		$rows = $this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('confirmed', TRUE);
		if (is_null($category)) {
			return $rows->count();
		} else {
			$counter = 0;			
			foreach ($rows as $row) {				
				$watch = $this->getWatchRepository()->getWatch($row->watch_id);
				
				if ($watch->getCategory() == $category) {
					$counter++;
				}
			}			
			return $counter;
		}
	}
	
	public function getToken($raceId) {
		return $this->database->table('race')
				->get($raceId)
				->token;
	}
	
	public function setToken($raceId, $token) {
		$this->database->table('race')
				->where('id', $raceId)
				->update(array(
					"token" => $token,
					"results_confirmed" => 0
				));
	}
	
	public function confirm($raceId, $token) {
		return $this->database->table('race')
				->where('id', $raceId)
				->where('token', $token)
				->update(array(
					"results_confirmed" => 1
				));		
	}
	
	public function getRacesByEditor(RaceRepository $repository, $userId) {
		$rows = $this->database->table('editor_race')
				->where('user_id', $userId);				
		$races = array();
		foreach ($rows as $row) {
			$race = $this->getRace($row->race_id);
			if ($race->season == $this->season) {
				$race->repository = $repository;
				$races[$race->id] = $race;
			}
		}
		return $races;
	}
	
	public function getRacesByOrganizer(RaceRepository $repository, $unitId) {
		$rows = $this->database->table('race')
				->where('organizer', $unitId)
				->where('season', $this->season);
		$races = array();
		foreach ($rows as $row) {
			$race = $this->loadFromActiveRow($row);
			$race->repository = $repository;
			$races[$race->id] = $race;
		}
		return $races;
	}
	
	public function getRacesByParticipant(RaceRepository $repository, $personId) {
		$rows = $this->database->table('participant')
				->where('person_id', $personId);
		$races = array();
		foreach ($rows as $row) {
			$raceIds = $this->database->table('participant_race')
					->where('participant_id', $row->id);
			foreach ($raceIds as $raceId) {
				$race = $this->getRace($raceId);				
				if ($race->season == $this->season) {
					$race->repository = $repository;
					$races[$race->id] = $race;
				}
			}
		}
		return $races;
	}	
	
	public function getPrevRace($watchId, Race $race) {
		$table = $this->database->table('race_watch')
				->where('watch_id', $watchId);		
		foreach ($table as $row) {
			$tmp = $this->database->table('race')
					->get($row->race_id);
			if ($tmp->advance == $race->id) {
				return $this->loadFromActiveRow($tmp);				
			}
		}
		throw new LogicException("Nenalezen předchozí závod hlídky");
	}
	
	public function deleteAdvancedWatchs($prevId, $advanceId) {
		$join = $this->database->table('race_watch')
				->where('race_id', $prevId)
				->where('advance', TRUE);
		foreach ($join as $row) {
			$participants = $this->database->table('participant')
					->where('watch', $row->watch_id);
			foreach ($participants as $participant) {
				$this->database->table('participant_race')
						->where('participant_id', $participant->id)
						->where('race_id', $advanceId)
						->delete();
			}
			$this->database->table('race_watch')
					->where('race_id', $advanceId)
					->where('watch_id', $row->watch_id)
					->delete();
		}
	}
}
