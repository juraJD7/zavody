<?php

/**
 * RaceDbMapper
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
	 * Vyrobí z továrny instanci třídy WatchRepository;
	 * 
	 * @return WatchRepository
	 */
	private function getWatchRepository() {
		return call_user_func($this->watchRepositoryFactory);
	}

	/**
	 * Vrátí závod
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
	
	/**
	 * Vrátí pole s počtem závodů pro každý kraj
	 * 
	 * Klíčem pole je ID regionu, hodnout počet závodů
	 * 
	 * @return int[] Počet závodů v jednotlivých regionech
	 */
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
	 * Vytvoří závod z řádku databáze
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
	 * Vrátí celostátní kolo pro aktuální ročník
	 * 
	 * @param RaceRepository $repository
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
	 * Vrátí seznam krajů
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
	
	/**
	 * Vrátí závody, kterých se účastní zadaná hlídka
	 * 
	 * @param RaceRepository $repository
	 * @param int $watchId ID hlídky
	 * @return Race[]
	 */
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
	 * Vrátí seznam závodů, ke kterým se lze přihlásit
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
	 * Vrátí záznam o druhu kola
	 * 
	 * @param int $id
	 * @return \Nette\Database\ActiveRow
	 */
	public function getRound($id) {
		$round = $this->database->table('race')->get($id)->round;
		return $this->database->table('round')->where('short', $round)->fetch();
	} 
	
	/**
	 * Vrátí jméno ročníku
	 * 
	 * Jméno je tvořeno názvem soutěže a rokem konání
	 * 
	 * @param int $seasonId
	 * @return \Nette\Database\ActiveRow
	 */
	public function getSeasonName($seasonId) {
		$season = $this->database->table('season')->get($seasonId);
		$competition = $this->database->table('competition')->get($season->competition)->short;
		return "$competition $season->year";
	}
	
	/**
	 * Vrátí záznam o kraji
	 * 
	 * @param int $id ID kraje
	 * @return \Nette\Database\ActiveRow
	 */
	public function getRegion($id) {
		$region = $this->database->table('race')->get($id)->region;		
		return $this->database->table('region')->get($region);
	}
	
	/**
	 * Vrátí záznam o povolenem rozsahu členů v závodě
	 * 
	 * @param int $id ID závodu
	 * @return \Nette\Database\ActiveRow
	 */
	public function getMembersRange($id) {
		$range = $this->database->table('race')->get($id)->members_range;
		return $this->database->table('members_range')->get($range);
	}
	
	/**
	 * Vrátí editory závodu
	 * 
	 * @param int $id ID závodu
	 * @return User[]
	 */
	public function getEditors($id) {
		$result = $this->database->table('editor_race')->where('race_id', $id);
		$editors = array();
		foreach ($result as $row) {
			$editors[] = $this->userRepository->getUser($row->user_id);
		}
		return $editors;
	}
	
	/**
	 * Vrátí uživatele, který založil závodu
	 * 
	 * @param int $id ID závodu
	 * @return User
	 */
	public function getAuthor($id) {
		$userId = $this->database->table('race')->get($id)->author;
		return $this->userRepository->getUser($userId);
	}

	/**
	 * Vrátí ID navazujícího závodu
	 * 
	 * @param int ID závodu
	 * @return int ID navazujícího závodu
	 */
	public function getAdvance($id) {		
		return $this->database->table('race')->get($id)->advance;		
	}
	
	/**
	 * Vrátí záznam o postupovém klíči závodu
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
	
	/**
	 * Vrátí nejzašší možné datum narození pro rádce
	 * 
	 * @param int $season ID ročníku
	 * @return DateTime
	 */	
	public function getGuideAge($season) {
		return $this->database->table('season')
				->get($season)
				->guide_age;
	}
	
	/**
	 * Vrátí nejzašší možné datum narození pro rádce
	 * 
	 * @param int $season ID ročníku
	 * @return DateTime
	 */	
	public function getRunnerAge($season) {
		return $this->database->table('season')
				->get($season)
				->runner_age;
	}
	
	/**
	 * Vrátí počet hlídek v závodě
	 * 
	 * @param int $raceId ID závodu
	 * @param string $category Název kategorie, omezí výběr pouze na danou kategorii
	 * @return int
	 */
	public function getNumWatchs($raceId, $category) {
		//výběr potvrzených hlídek závodu
		$rows = $this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('confirmed', TRUE);
		if (is_null($category)) {
			return $rows->count();
		} else {
			//pokud je omezeno kategorií, zjistí se kategorie při shodě je navýšen počet
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
	
	/**
	 * Vrátí potvrzovací token závodu
	 * 
	 * @param int $raceId
	 * @return string
	 */
	public function getToken($raceId) {
		return $this->database->table('race')
				->get($raceId)
				->token;
	}
	
	/**
	 * Uloží do databáze token k potvrzení výsledků a zároveň nastaví příznak výsledků jako nepotvrzené
	 * 
	 * @param int $raceId
	 * @param string $token
	 */
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
	
	/**
	 * Vrátí pole závodů v aktuálním ročníku, kde je zadaný uživatel editorem
	 * 
	 * @param RaceRepository $repository
	 * @param int $userId ID uživatele	
	 * @return Race[]
	 */
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
	
	/**
	 * Vrátí pole závodů, které organizuje zadaná jednotka v aktuálním ročníku
	 * 
	 * @param RaceRepository $repository
	 * @param int $unitId ID jednotky
	 * @return Race[]
	 */
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
	
	/**
	 * Vrátí pole závodů v aktuálním ročníku, kterého se účastní zadaná osoba
	 * 
	 * @param RaceRepository $repository
	 * @param int $personId ID jednotky
	 * @return Race[]
	 */
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
	
	/**
	 * Vrátí závod, ze kterého hlídka postoupila do zadaného kola
	 * 
	 * @param int $watchId ID hlídky
	 * @param Race $race navazující závod
	 * @return Race přecházející závod
	 * @throws LogicException
	 */
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
	
	/**
	 * Smaže postupující hlídky závodu z navazujícího kola
	 * 
	 * @param int $prevId ID předcházejícího závodu hlídky
	 * @param int $advanceId ID navazujícího závodu hlídy, ze kterého jsou hlídky a členové mazáni
	 */
	public function deleteAdvancedWatchs($prevId, $advanceId) {
		//zjištění postupujících hlídek
		$join = $this->database->table('race_watch')
				->where('race_id', $prevId)
				->where('advance', TRUE);
		foreach ($join as $row) {
			//smazání všech členů hlídky z navazujího závodu
			$participants = $this->database->table('participant')
					->where('watch', $row->watch_id);
			foreach ($participants as $participant) {
				$this->database->table('participant_race')
						->where('participant_id', $participant->id)
						->where('race_id', $advanceId)
						->delete();
			}
			//smazání hlídky z navazujího závodu
			$this->database->table('race_watch')
					->where('race_id', $advanceId)
					->where('watch_id', $row->watch_id)
					->delete();
		}
	}
}
