<?php

/**
 * Description of RaceDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class RaceDbMapper extends BaseDbMapper {
	
	private $unitRepository;

	/**
	 * 
	 * @param \Nette\Database\Context $database
	 * @param \UnitRepository $unitRepository
	 * @param \UserManager $userManager
	 */	
	public function __construct(\Nette\Database\Context $database, \UnitRepository $unitRepository, \UserManager $userManager) {
		parent::__construct($database, $userManager);
		
		$this->unitRepository = $unitRepository;
	}

	/**
	 * 
	 * @param int $id
	 * @return Race
	 */
	public function getRace($id) {
		$row = $this->database->table('race')->get($id);		
		if(!$row) {
			throw new Nette\InvalidArgumentException("Závod $id neexistuje");
		}
		return $this->loadFromActiveRow($row);
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
		$race->gpsX = $row->gps_x;
		$race->gpsY = $row->gps_y;
		$race->commander = $row->commander;
		$race->referee = $row->referee;
		$race->telephone = $row->telephone;
		$race->email = $row->email;
		$race->web = $row->web;
		$race->capacity = $row->capacity;
		$race->applicationDeadline = $row->application_deadline;
		$race->key = $row->key;
		$race->targetGroup = $row->target_group;		
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
				->where('date >', date('Y-m-d'))
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
	 * 
	 * @param int $id
	 * @return \Nette\Database\ActiveRow
	 */
	public function getRound($id) {
		$round = $this->database->table('race')->get($id)->round;
		return $this->database->table('round')->where('short', $round)->fetch();
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
			$editors[] = $this->userManager->load($row->user_id);
		}
		return $editors;
	}
	
	public function getAuthor($id) {
		$userId = $this->database->table('race')->get($id)->author;
		return $this->userManager->load($userId);
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
	
}
