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
	 * @var array [Person, idRace, idRole]
	 */
	private $members = array();
	
	//private $oddily = array();
	//private $season; - pozná se podle závodů	
	
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
			if ($participant["roleId"] == 1 || $participant["roleId"] == 2) { // ucastnik nebo radce
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
			switch ($participant["roleId"]) {
				case Person::TYPE_RUNNER : $runner++;
				case Person::TYPE_GUIDE : $guide++;
				default : $escort++;
			}
			if ($participant["roleId"] != Person::TYPE_ESCORT) {
				if ($participant["member"]->sex == Person::ID_MALE) {
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
		$this->repository->getPoints($this->id, $raceId);
	}
	
	public function getOrder($raceId) {
		$this->repository->getOrder($this->id, $raceId);
	}
	
	public function isConfirmed($raceId) {
		$this->repository->isConfirmed($this->id, $raceId);
	}
	
	public function getAdvance(\Race $race) {		
		return ($this->getOrder($race->id) <= $race->getNumAdvance($this->getCategory()));			
	}	
	
	public function addMember(Person $member, $raceId, $roleId) {
		if (!$this->isInRace($raceId)) {
			throw new Nette\InvalidArgumentException("Nelze přidat člena do závodu, kterého se hlídka neúčastní");
		}
		$memberArray = Array(
			"member" => $member,
			"raceId" => $raceId,
			"roleId" => $roleId,
			"role" => $this->repository->getRoleName($roleId)
		);
		array_push($this->members, $memberArray);
	}
	
	/**
	 * 
	 * @param Race $race
	 * @return array [Person member, int raceId, int roleId]
	 */
	public function getMembers($race = null) {
		if (empty($this->members)) {
			if (isset($this->id)) {
				$this->members = $this->repository->getMembers($this->id, $race);
			}
		}
		$members = array();
		if (is_null($race)) {
			$raceId = $this->getRaces()[0]->id;
		} else {
			$raceId = $race->id;
		}		
		foreach ($this->members as $member) {
			if ($member["raceId"] == $raceId) {
				$members[] = $member;
			}
		}
		return $members;
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
		return $this->nonCompetitiveReason;
	}
	
}
