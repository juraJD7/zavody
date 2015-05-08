<?php

/**
 * Description of Watch
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Watch extends Nette\Object {
	
	const CATEGORY_MALE = "chlapecká";
	const CATEGORY_FEMALE = "dívčí";
	const CATEGORY_NONCOMPETIVE = "nesoutěžní";
	
	private $repository;
	
	private $id;
	private $name;
	private $author;
	private $group;	
	private $troop;	
	private $town; //obec?
	private $emailLeader;
	private $emailGuide; //radce
	private $races = array();
	private $category;
	private $nonCompetitiveReason;
	
	/**
	 *
	 * @var array Person
	 */
	private $members = array();	
	
	public function __construct($id = null) {
		$this->id = $id;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setRepository($repository) {
		$this->repository = $repository;
	}

	public function getName() {
		return $this->name;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getAuthor() {
		if (is_null($this->author)) {
			$this->author = $this->repository->getAuthor($this->id);
		}
		return $this->author;
	}
	
	public function setAuthor(User $author) {
		$this->author = $author;
	}


	public function getGroup() {
		if (is_null($this->group)) {
			$this->group = $this->repository->getUnit($this->id, "group");
		}
		return $this->group;
	}
	
	public function setGroup(Unit $unit) {
		$this->group = $unit;
	}

	public function getTroop() {
		if (is_null($this->troop)) {
			$this->troop = $this->repository->getUnit($this->id, "troop");
		}
		return $this->troop;
	}
	
	public function setTroop(Unit $unit) {
		$this->troop = $unit;
	}
	
	public function getTown() {
		return $this->town;
	}
	
	public function setTown($town) {
		$this->town = $town;
	}
	
	public function getEmailLeader() {
		return $this->emailLeader;
	}
	
	public function setEmailLeader($email) {
		$this->emailLeader = $email;
	}
	
	public function getEmailGuide() {
		return $this->emailGuide;
	}
	
	public function setEmailGuide($email) {
		$this->emailGuide = $email;
	}
	
	public function getRaces() {
		if (empty($this->races)) {
			$this->races = $this->repository->getRaces($this->id);
		}
		return $this->races;
	}
	
	public function addRace(Race $race) {
		$this->getRaces();
		array_push($this->races, $race);
	}
	
	public function getNumRunners(Race $race) {
		$participants = $this->getMembers($race);
		$counter = 0;
		foreach ($participants as $participant) {
			if ($participant->getRoleId($race->id) == Person::TYPE_GUIDE || $participant->getRoleId($race->id) == Person::TYPE_RUNNER) { // ucastnik nebo radce
				$counter++;
			}
		}
		return $counter;
	}

	/**
	 * Vrátí kategorii - nesoutěžní, chlapeckou, dívčí.
	 * 
	 * Pokud je nastaveno, kategorie je již daná a nelze měnit
	 * Pokud není nastaveno, kategorie se pružně počítá podle členů
	 * 
	 */
	public function getCategory() {
		if (!is_null($this->category)) {
			return $this->category;
		} else {
			// pokud je družina přihlášena na 2. závod, musí mít předtím jasně danou kategorii
			if (count($this->getRaces()) > 1) {				
				throw new LogicException("Není záznam o kategorii z posupového kola kola");
			}
			$race = $this->getRaces()[0];			
			$participants = $this->getMembers($race);
			return $this->countParticipants($participants, $race);
		}
	}
	
	private function countParticipants($participants, Race $race) {
		$runner = 0;
		$guide = 0;
		$escort = 0;
		$male = 0;
		$female = 0;		
		foreach ($participants as $participant) {			
			switch ($participant->getRoleId($race->id)) {			
				case Person::TYPE_RUNNER : $runner++;break;
				case Person::TYPE_GUIDE : $guide++;break;
				default : $escort++;
			}			
			if ($participant->getRoleId($race->id) != Person::TYPE_ESCORT) {				
				if ($participant->sexId == Person::ID_MALE) {
					$male++;
				} else {
					$female++;
				}
			}
		}		
		if ($guide > 1) {
			$this->nonCompetitiveReason = "Počet rádců překročen, maximum je 1. Zbytek označ jako doprovod";
			return Watch::CATEGORY_NONCOMPETIVE;
		}
		if ($runner + $guide > $race->maxRunner) {
			$this->nonCompetitiveReason = "Počet členů hlídky překročen. Maximum pro tento závod je " . $race->maxRunner;
			return Watch::CATEGORY_NONCOMPETIVE;
		}
		if ($runner + $guide < $race->minRunner) {
			$this->nonCompetitiveReason = "Počet členů je menší než minimum pro tento závod (" . $race->minRunner . ")";
			return Watch::CATEGORY_NONCOMPETIVE;
		}		
		if ($male > $female) {
			return Watch::CATEGORY_MALE;
		} else {
			return Watch::CATEGORY_FEMALE;
		}
	}

	public function setCategory($category) {
		if (is_null($this->category)) {
			$this->category = $category;
		} else {
			throw new LogicException("Nelze změnit již nastavenou kategorii");
		}
	}	
		
	public function getPoints($raceId) {
		return $this->repository->getPoints($this->id, $raceId);
	}
	
	public function getNote($raceId) {
		return $this->repository->getNote($this->id, $raceId);
	}
	
	public function getOrder($raceId) {
		return $this->repository->getOrder($this->id, $raceId);
	}
	
	public function getAdvance($raceId) {
		$advance = $this->repository->getAdvance($this->id, $raceId);
		if ($advance) {
			return "Ano";
		} 
		return "Ne";
	}
	
	public function isConfirmed($raceId) {
		return $this->repository->isConfirmed($this->id, $raceId);
	}	
	
	public function addMember(Person $member) {
		$isInRace = FALSE;
		foreach ($member->races as $raceId) {
			if ($this->isInRace($raceId)) {
				$isInRace = TRUE;
				break;
			}
		}
		if ($isInRace) {
			array_push($this->members, $member);
		} else {
			throw new LogicException("Osoba $member->personId se neúčastní žádného ze závodů hlídky");
		}
	}
	
	/**
	 * 
	 * @param Race $race
	 * @return array Person
	 */
	public function getMembers($race = null) {
		if (empty($this->members)) {			
			$this->members = $this->repository->getMembers($this->id, $race);
		}		
		return $this->members;
	}


	public function isInRace($raceId) {
		$this->getRaces();
		foreach ($this->races as $race) {
			if($race->id == $raceId) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	public function getNonCompetitiveReason() {
		$this->getCategory();
		return $this->nonCompetitiveReason;
	}
	
	public function deleteAllMembers($raceId = NULL) {
		if ($this->repository->deleteAllMembers($this->id, $raceId)) {
			$this->members = array();
		}
	}
	
	public function save() {
		$this->repository->save($this);
	}
	
	public function fixCategory() {
		$this->repository->fixCategory($this->id, $this->getCategory());
	}
	
	public function processAdvance(Race $race) {
		$this->repository->processAdvance($this, $race);
	}
	
	public function getToken($raceId) {
		return $this->repository->getToken($this->id, $raceId);
	}
	
	public function setToken($raceId, $token) {
		$this->repository->setToken($this->id, $raceId, $token);
	}
	
	public function confirm($token) {
		return $this->repository->confirm($this->id, $token);
	}
	
	public function getSeasonName() {
		if ($this->getRaces()) {
			return $this->repository->getSeasonName($this->races[0]->season);
		}
		return NULL;
	}
	
	public function canEdit(Nette\Security\LoggedUser $user) {
		if ($user->isInRole('admin')) {
			return TRUE;
		}
		if (($this->troop == $user->getUnit() || $this->group == $user->getUnit())
				&& $user->isOfficial()) {
			return TRUE;
		}
		if ($user->isInRole('raceManager') && $this->isInRace($user->race)) {
			return TRUE;
		}
		$this->getAuthor();
		if ($this->author->id == $user->id) {
			return TRUE;
		}
		return FALSE;
		
	}
}
