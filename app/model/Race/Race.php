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
	private $gpsX;
	private $gpsY;
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
	
	private $editors = array();	
	
	public function __construct($id) {
		if(!is_int($id)) {
			throw new \Nette\MemberAccessException("Parametr id musí být integer.");
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
			throw new \Nette\MemberAccessException("Parametr season musí být integer.");
		}
		$this->season = $season;
	}
	
	public function getName() {
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
	/*
	public function setRound($round) {
		if(!ctype_alpha($round) || strlen($round) != 1) {
			throw new \Nette\MemberAccessException("Parametr id musí round 1 znak.");
		}
		$this->round = $round;
	}*/
	
	public function getRegion() {
		if (is_null($this->region)) {
			$this->region = $this->repository->getRegion($this->id);
		}
		return $this->region;
	}
	/*
	public function setRegion($region) {
		if(!is_int($region)) {
			throw new \Nette\MemberAccessException("Parametr region musí round integer.");
		}
		$this->region = $region;
	}
	*/
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
	
	public function getGpsY() {
		return $this->gpsY;
	}

	public function setGpsY($gpsY) {
		if(!is_double($gpsY)) {
			throw new \Nette\MemberAccessException("Parametr gps_y musí byt cislo.");
		}
		$this->gpsY = $gpsY;
	}
	
	public function getGpsX() {
		return $this->gpsX;
	}

	public function setGpsX($gpsX) {
		if(!is_double($gpsX)) {
			throw new \Nette\MemberAccessException("Parametr gps_x musí byt cislo.");
		}
		$this->gpsX = $gpsX;
	}
	
	public function getCommander() {
		return $this->commander;
	}

	public function setCommander($commander) {		
		$this->commander = $commander;
	}
	
	public function getReferee() {
		return $this->referee;
	}

	public function setReferee($referee) {		
		$this->referee = $referee;
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
			throw new \Nette\MemberAccessException("Parametr capacity musí round integer.");
		}
		$this->capacity = $capacity;
	}
	
	public function getKey() {
		return $this->key;
	}

	public function setKey($key) {
		if(!is_int($key)) {
			throw new \Nette\MemberAccessException("Parametr key musí round integer.");
		}
		$this->key = $key;
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
	
	/*
	public function setMembersRange($memberRange) {
		if(!is_int($memberRange)) {
			throw new \Nette\MemberAccessException("Parametr membersRange musí round integer.");
		}
		$this->membersRange = $memberRange;
	}*/
	
	public function getEditors() {
		if (empty($this->editors)) {
			$this->editors = $this->repository->getEditors($this->id);
		}
		return $this->editors;
	}
	
	public function getTitle() {
		$round = $this->getRound();		
		return " ($round->name kolo pořádané jednotkou " . $this->getOrganizer()->displayName . ")";		
	}
	
	public function getNumWatchs($category = NULL) {
		//not implemented
		return 0;
	}
	
	public function getNumAdvance($category = NULL) {
		//not implemented
		return 0;
	}
	
	public function canEdit($userId) {		
		foreach ($this->getEditors() as $editor) {
			if ($userId == $editor->id) {
				return TRUE;
			}
		}
		return FALSE;
	}	
	
}
