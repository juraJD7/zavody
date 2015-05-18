<?php

/**
 * Description of WatchRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class WatchRepository extends Nette\Object {
	
	private $raceRepositoryFactory;
	private $personRepositoryFactory;
	private $userRepository;
	private $unitRepository;
	private $dbMapperFactory;
	
	/**
	 * 
	 * @param $raceRepositoryFactory
	 * @param $personRepositoryFactory
	 * @param UnitRepository $unitRepository
	 * @param UserRepository $userRepository
	 * @param $dbMapperFactory
	 */
	public function __construct($raceRepositoryFactory, $personRepositoryFactory, UnitRepository $unitRepository, UserRepository $userRepository, $dbMapperFactory) {
		$this->raceRepositoryFactory = $raceRepositoryFactory;
		$this->personRepositoryFactory = $personRepositoryFactory;
		$this->unitRepository = $unitRepository;
		$this->userRepository = $userRepository;
		$this->dbMapperFactory = $dbMapperFactory;
		
	}
	
	/**
	 * 
	 * @return WatchDbMapper
	 */
	private function getDbMapper() {
		return call_user_func($this->dbMapperFactory);
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
	

	/**
	 * 
	 * @param int $id
	 * @return Watch
	 */
	public function getWatch($id) {
		$watch = $this->getDbMapper()->getWatch($id, $this);
		return $watch;
	}
	
	public function getWatchs($raceId) {
		return $this->getDbMapper()->getWatchs($raceId, $this);
	}

	public function getAuthor($id) {
		return $this->getDbMapper()->getAuthor($id);
	}
	
	/**
	 * 
	 * @param int $id jednotky
	 * @param int $type unitId jednotky (družina, ...)
	 * @return Unit
	 */
	public function getUnit($id, $type) {
		return $this->getDbMapper()->getUnit($id, $type);
	}	
	
	/**
	 * Vrátí členy hlídky
	 * 
	 * @param int $watchId
	 * @param Race $race omezí výběr členů na konkrétní závod
	 * @return Person[]
	 */
	public function getMembers($watchId, Race $race = null) {
		if (is_null($race)) {
			$raceId = null;
		} else {
			$raceId = $race->id;
		}
		return $this->getPersonRepository()->getPersonsByWatch($watchId, $raceId);
	}
	
	/**
	 * Vrátí závody, kterých se hlídka účastní
	 * 
	 * @param int $watchId
	 * @return Race[]
	 */
	public function getRaces($watchId) {
		//získá ID závodů, kterých se účastní hlídka
		$result = $this->getDbMapper()->getRaces($watchId);
		// načtení závodů
		$races = array();
		foreach ($result as $id) {
			$races[] = $this->getRaceRepository()->getRace($id);
		}
		return $races;
	}
	
	public function getPoints($watchId, $raceId) {
		return $this->getDbMapper()->getPoints($watchId, $raceId);
	}
	
	public function getNote($watchId, $raceId) {
		return $this->getDbMapper()->getNote($watchId, $raceId);
	}
	
	public function getOrder($watchId, $raceId) {
		return $this->getDbMapper()->getOrder($watchId, $raceId);
	}
	
	public function getAdvance($watchId, $raceId) {
		return $this->getDbMapper()->getAdvance($watchId, $raceId);
	}
	
	public function isConfirmed($watchId, $raceId) {
		return $this->getDbMapper()->isConfirmed($watchId, $raceId);
	}
	
	/**
	 * Vytvoří ze session obraz Hlídky (bez id)
	 * 
	 * @param Nette\Http\SessionSection $section
	 * @return \Watch
	 * @throws SessionExpiredException
	 */
	public function createWatchFromSession(Nette\Http\SessionSection $section) {
		if (!$section->offsetExists("basic")) {
			throw new SessionExpiredException("Platnost údajů vypršela.");
		}
		//záskání základních údajů ze session
		$basic = $section->basic;
		$watch = new Watch();
		$watch->author = $this->userRepository->getUser($basic["author"]);
		$watch->repository = $this;
		$watch->addRace($this->getRaceRepository()->getRace($basic["race"]));		
		$watch->name = $basic["name"];
		$watch->troop = $this->unitRepository->getUnit($basic["troop"]);
		$watch->group = $this->unitRepository->getUnit($basic["group"]);
		$watch->town = $basic["town"];
		$watch->emailLeader = $basic["email_leader"];
		$watch->emailGuide = $basic["email_guide"];
		//načtení členů hlídky jako účastníků závodu a přidání do hlídky
		$members = ($section->members) ? $section->members : array();
		foreach ($members as $key => $value) {
			$member = $this->getPersonRepository()->getPerson($key);
			$member->unit = $this->unitRepository->getUnit($section->units[$key]);
			$member->addRace($basic["race"], $section->roles[$key]);
			$watch->addMember($member);
		}
		return $watch;
	}
	
	/**
	 * Vrátí hlídku ve formě vhodné pro formulář k úpravě
	 * 
	 * @param Watch $watch
	 * @param int $raceId
	 * @return array
	 */
	public function getDataForForm(Watch $watch, $raceId = NULL) {
		if (is_null($raceId)) {
			$races = $watch->getRaces();			
			$raceId = $races[0]->id;
		}
		$raceArray = array (
			"race" => $raceId,
			"author" => $watch->author->id,
			"name" => $watch->name,
			"troop" => $watch->troop->id,			
			"group" => $watch->group->id,
			"town" => $watch->town,
			"email_guide" => $watch->emailGuide,
			"email_leader" => $watch->emailLeader			
		);
		return $raceArray;
	}

	public function save(Watch $watch) {
		return $this->getDbMapper()->save($watch);
	}
	
	public function deleteAllMembers($watchId, $raceId = NULL) {
		return $this->getDbMapper()->deleteAllMembers($watchId, $raceId);
	}
	
	/**
	 * Ověří, zda může být osoba členem hlídky s danou rolí
	 * 
	 * @param int $personId ID osoby ze skautISu
	 * @param int $roleId ID role v závodě
	 * @param Race $race 
	 * @param int $watchId ID hlídky
	 * @return boolean
	 */
	public function validateMember($personId, $roleId, $race, $watchId = NULL) {
		return $this->getDbMapper()->validateMember($personId, $roleId, $race, $watchId);
	}
	
	public function fixCategory($watchId, $category) {
		$this->getDbMapper()->fixCategory($watchId, $category);
	}
	
	/**
	 * Provede postup hlídky do dalšího kola
	 * 
	 * @param Watch $watch Postupující hlídka
	 * @param Race $race Kolo, ze kterého hlídka postupuje
	 */
	public function processAdvance(Watch $watch, Race $race) {
		$this->getDbMapper()->processAdvance($watch, $race);
	}
	
	/**
	 * Vrátí token pro potvrzení účasti hlídky v závodě
	 * 
	 * @param int $watchId
	 * @param int $raceId
	 * @return string
	 */
	public function getToken($watchId, $raceId) {
		return $this->getDbMapper()->getToken($watchId, $raceId);
	}	
	
	public function setToken($watchId, $raceId, $token) {
		$this->getDbMapper()->setToken($watchId, $raceId, $token);
	}
	
	public function confirm($watchId, $token) {
		return $this->getDbMapper()->confirm($watchId, $token);
	}
	
	public function getWatchsByAuthor($userId) {
		return $this->getDbMapper()->getWatchsByAuthor($this, $userId);
	}
	
	public function getWatchsByUnit($unitId) {
		return $this->getDbMapper()->getWatchsByUnit($this, $unitId);
	}
	
	public function getWatchsByParticipant($personId) {
		return $this->getDbMapper()->getWatchsByParticipant($this, $personId);
	}	
	
	public function getSeasonName($seasonId) {
		return $this->getDbMapper()->getSeasonName($seasonId);
	}
	
	public function deleteWatch($watchId, $raceId) {
		return $this->getDbMapper()->deleteWatch($watchId, $raceId);
	}
	
	public function unsetAdvance(Watch $watch, Race $prevRace) {
		return $this->getDbMapper()->unsetAdvance($this, $watch, $prevRace);
	}
}
