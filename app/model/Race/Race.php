<?php

/**
 * Description of Race
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Race extends \Nette\Object {
	
	private $repository;
	
	private $id;
	private $season;
	private $name;
	private $description;
	private $author;
	private $round;
	private $region;
	private $advance;
	private $organizer;
	private $date;
	private $place;	
	private $commander;
	private $referee;
	private $telephone;
	private $email;
	private $web;
	private $capacity;
	private $applicationDeadline;
	private $key;
	private $targetGroup;
	private $membersRange;
	private $commanderEmail;
	private $refereeEmail;
	
	private $editors = array();	
	
	public function __construct($id) {
		if(!is_int($id)) {
			throw new \Nette\InvalidArgumentException("Parametr id musí být integer.");
		}
		$this->id = $id;		
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setRepository(\RaceRepository $repository) {
		$this->repository = $repository;
	}
	
	public function getSeason() {
		return $this->season;
	}
	
	public function setSeason($season) {
		if(!is_int($season)) {
			throw new \Nette\InvalidArgumentException("Parametr season musí být integer.");
		}
		$this->season = $season;
	}
	
	public function getSeasonName() {
		return $this->repository->getSeasonName($this->season);
	}
	
	public function getName() {
		if (empty($this->name)) {
			return "Kolo bez názvu";
		}
		return $this->name;
	}

	public function setName($name) {		
		$this->name = $name;
	}
	
	public function getDescription() {
		return $this->description;
	}

	public function setDescription($description) {		
		$this->description = $description;
	}
	
	public function getAuthor() {
		if (is_null($this->author)) {
			$this->author = $this->repository->getAuthor($this->id);
		}
		return $this->admin;
	}
	
	public function getRound() {
		if (is_null($this->round)) {
			$this->round = $this->repository->getRound($this->id);
		}
		return $this->round;
	}	
	
	public function getRegion() {
		if (is_null($this->region)) {
			$this->region = $this->repository->getRegion($this->id);
		}
		return $this->region;
	}
	
	public function getAdvance() {
		if (is_null($this->advance)) {
			$this->advance = $this->repository->getAdvance($this->id);
		}
		return $this->advance;
	}
	
	public function getOrganizer() {
		if (is_null($this->organizer)) {
			$this->organizer = $this->repository->getOrganizer($this->id);
		}
		return $this->organizer;
	}
	
	public function getDate() {
		return $this->date;
	}

	public function setDate(DateTime $date) {		
		$this->date = $date;
	}
	
	public function getPlace() {
		return $this->place;
	}

	public function setPlace($place) {		
		$this->place = $place;
	}
	
	public function getCommander() {
		return $this->commander;
	}

	public function setCommander($commander) {		
		$this->commander = $commander;
	}
	
	public function getCommanderEmail() {
		return $this->commanderEmail;
	}

	public function setCommanderEmail($email) {		
		$this->commanderEmail = $email;
	}	
	
	public function getReferee() {
		return $this->referee;
	}

	public function setReferee($referee) {		
		$this->referee = $referee;
	}
	
	public function getRefereeEmail() {
		return $this->refereeEmail;
	}

	public function setRefereeEmail($email) {		
		$this->refereeEmail = $email;
	}
	
	public function getTelephone() {
		return $this->telephone;
	}

	public function setTelephone($telephone) {		
		$this->telephone = $telephone;
	}
	
	public function getEmail() {
		return $this->email;
	}

	public function setEmail($email) {		
		$this->email = $email;
	}
	
	public function getWeb() {
		return $this->web;
	}

	public function setWeb($web) {		
		$this->web = $web;
	}
	
	public function getCapacity() {
		return $this->capacity;
	}

	public function setCapacity($capacity) {
		if(!is_int($capacity)) {
			throw new \Nette\InvalidArgumentException("Parametr capacity musí round integer.");
		}
		$this->capacity = $capacity;
	}
	
	public function getKey() {
		if (is_null($this->key)) {
			$this->key = $this->repository->getKey($this->id);
		}		
		return $this->key;
	}
	
	public function getApplicationDeadline() {
		return $this->applicationDeadline;
	}

	public function setApplicationDeadline(DateTime $deadline) {		
		$this->applicationDeadline = $deadline;
	}
	
	public function getTargetGroup() {
		return $this->telephone;
	}

	public function setTargetGroup($target) {		
		$this->targetGroup =  $target;
	}
	
	public function getMembersRange() {
		if (is_null($this->membersRange)) {
			$this->membersRange = $this->repository->getMembersRange($this->id);
		}
		return $this->membersRange;
	}	
		
	public function getEditors() {
		if (empty($this->editors)) {
			$this->editors = $this->repository->getEditors($this->id);
		}
		return $this->editors;
	}
	
	public function getTitle() {
		$round = $this->getRound();		
		return "$round->name kolo pořádané jednotkou " . $this->getOrganizer()->displayName;		
	}
	
	public function getGuideAge() {
		return $this->repository->getGuideAge($this->season);
	}
	
	public function getRunnerAge() {
		return $this->repository->getRunnerAge($this->season);
	}
	
	public function getMinRunner() {
		return $this->repository->getMinRunner($this->getMembersRange());
	}
	
	public function getMaxRunner() {
		return $this->repository->getMaxRunner($this->getMembersRange());
	}

	public function getNumWatchs($category = NULL) {		
		return $this->repository->getNumWatchs($this->id, $category);
	}
	
	public function getNumAdvance($category) {
		if ($this->getRound()->short == 'C') {
			return 0;
		} else if ($this->getRound()->short == 'K') {
			return 1;
		} else {
			$numWatchs = $this->getNumWatchs($category);			
			if ($numWatchs > 20) {
				$numWatchs = 20;
			}
			if ($numWatchs == 0) {
				return 0;
			}
			$key = $this->getKey();			
			return $key[$numWatchs];			
		}
	}
	
	public function canEdit($userId) {		
		foreach ($this->getEditors() as $editor) {
			if ($userId == $editor->id) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	public function isLoginActive($season) {		
		if ($this->getNumWatchs() < $this->capacity 
				&& date('Y-m-d') < $this->applicationDeadline
				&& $this->season == $season
				&& $this->getRound()->short == 'Z'
				) {
			return TRUE;
		} else {
			return FALSE;
		}
		
			
	}
	
	public function getToken() {
		return $this->repository->getToken($this->id);
	}
	
	public function setToken($token) {
		$this->repository->setToken($this->id, $token);
	}
	
	public function confirm($token) {
		return $this->repository->confirm($this->id, $token);
	}
	
}
