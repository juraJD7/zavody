<?php

/**
 * Description of WatchRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class WatchRepository {
	
	private $raceRepository;
	private $personRepository;
	private $unitRepository;
	private $dbMapper;
	
	/**
	 * 
	 * @param RaceRepository $raceRepository
	 * @param $personRepositoryFactory
	 * @param UnitRepository $unitRepository
	 * @param WatchDbMapper $dbMapper
	 */
	public function __construct(RaceRepository $raceRepository, $personRepositoryFactory, UnitRepository $unitRepository, WatchDbMapper $dbMapper) {
		$this->raceRepository = $raceRepository;
		$this->personRepository = $personRepositoryFactory;
		$this->unitRepository = $unitRepository;
		$this->dbMapper = $dbMapper;
	}
	
	/**
	 * 
	 * @param int $id
	 * @return Watch
	 */
	public function getWatch($id) {
		$watch = $this->dbMapper->getWatch($id);
		$watch->repository = $this;
		return $watch;
	}

	public function getAuthor($id) {
		return $this->dbMapper->getAuthor($id);
	}
	
	/**
	 * 
	 * @param int $id jednotky
	 * @param int $type unitId jednotky (družina, ...)
	 * @return Unit
	 */
	public function getUnit($id, $type) {
		return $this->dbMapper->getUnit($id, $type);
	}	
	
	public function getMembers($watchId, Race $race) {
		return $this->personRepository->getPersonsByWatch($watchId, $race->id);
	}
	
	public function getRaces($watchId) {
		$result = $this->dbMapper->getRaces($watchId);
		$races = array();
		foreach ($result as $id) {
			$races[] = $this->raceRepository->getRace($id);
		}
		return $races;
	}
	
	public function getPoints($watchId, $raceId) {
		return $this->dbMapper->getPoints($watchId, $raceId);
	}
	
	public function getOrder($watchId, $raceId) {
		return $this->dbMapper->getOrder($watchId, $raceId);
	}
	
	public function isConfirmed($watchId, $raceId) {
		return $this->dbMapper->isConfirmed($watchId, $raceId);
	}
	
	public function getRoles() {
		return $this->dbMapper->getRoles();
	}
	
	public function getRoleName($id) {
		return $this->dbMapper->getRoleName($id);
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
		$basic = $section->basic;
		$watch = new Watch();
		$watch->repository = $this;
		$watch->addRace($this->raceRepository->getRace($basic["race"]));		
		$watch->name = $basic["name"];
		$watch->troop = $this->unitRepository->getUnit($basic["troop"]);
		$watch->group = $this->unitRepository->getUnit($basic["group"]);
		$watch->town = $basic["town"];
		$watch->emailLeader = $basic["email_leader"];
		$watch->emailGuide = $basic["email_guide"];
		
		foreach ($section->members as $key => $value) {
			$member = $this->personRepository->getPerson($key);
			$member->unit = $this->unitRepository->getUnit($section->units[$key]);
			$watch->addMember($member, $basic["race"], $section->roles[$key]);
		}
		return $watch;
	}
	
	public function getDataForForm(Watch $watch, $raceId = NULL) {
		if (is_null($raceId)) {
			$races = $watch->getRaces();
			if (count($races) != 1) {
				throw new LogicException("Nejdve vytvořit přihlášku ke 2 závodům zaráz.");
			}
			$raceId = $races[0]->id;
		}
		$raceArray = array (
			"race" => $raceId,
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
		return $this->dbMapper->save($watch);
	}
}
