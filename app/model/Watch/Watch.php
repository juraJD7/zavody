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
	
	/**
	 *
	 * @var int 
	 */
	private $id;
	
	/**
	 *
	 * @var string 
	 */
	private $name;
	
	/**
	 *
	 * @var User 
	 */
	private $author;
	
	/**
	 * Středisko hlídky
	 * 
	 * @var Unit 
	 */
	private $group;
	
	/**
	 * Oddíl hlídky
	 * 
	 * @var Unit 
	 */
	private $troop;
	
	/**
	 * Obec hlídky
	 * 
	 * @var string 
	 */
	private $town;
	
	/**
	 * Email na vůdce oddílu
	 * 
	 * @var string 
	 */
	private $emailLeader;
	
	/**
	 * Email na rádce hlídky
	 * 
	 * @var string 
	 */
	private $emailGuide;
	
	/**
	 * pole závodů, kterých se hlídka účastní
	 * 
	 * @var Race[] 
	 */
	private $races = array();
	
	/**
	 * soutěžní kategorie
	 * 
	 * @var string
	 */
	private $category;
	
	/**
	 * V případě nesoutěžní hlídky důvod, proč není soutěžní
	 * 
	 * @var string
	 */
	private $nonCompetitiveReason;
	
	/**
	 * pole členů hlídky napříč všemi koly
	 * 
	 * @var Person[]
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
	
	/**
	 * Vrátí počet soutěžících členů v hlídce v daném závodě
	 * 
	 * Jako soutěžící jsou počítání rádci a účastníci
	 * 
	 * @param Race $race
	 * @return int
	 */
	public function getNumRunners(Race $race) {
		$participants = $this->getMembers($race);
		$counter = 0;
		foreach ($participants as $participant) {
			// ucastnik nebo radce se počítá jako soutěžící
			if ($participant->getRoleId($race->id) == Person::TYPE_GUIDE || $participant->getRoleId($race->id) == Person::TYPE_RUNNER) { 
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
	
	/**
	 * Spočítá členy v družině a podle počtu členů a jejich pohlaví vrátí kategorii
	 * 
	 * V případě nesoutěžní kategorie uloží do atributu důvod
	 * 
	 * @param type $participants
	 * @param Race $race
	 * @return type
	 */
	public function countParticipants($participants, Race $race) {
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
		// ověření, zda hlídka splňuje pravidla pro soutěžní hlídku
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
		// hlídka je soutěžní
		// v případě rovnosti pohlaví je hlídka dívčí
		if ($male > $female) {
			return Watch::CATEGORY_MALE;
		} else {
			return Watch::CATEGORY_FEMALE;
		}
	}
	
	/**
	 * Nastaví kategorii napevno, poté již nelze (v dalších závodech) měnit
	 * 
	 * V případě, že je již kategorie nastavena, vyhodí výjmku - kategorie nelze změnit
	 * 
	 * @param string $category
	 * @throws LogicException
	 */
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
	
	/**
	 * Vrátí informaci o postupu v textové podobě
	 * 
	 * @param type $raceId
	 * @return string
	 */
	public function getAdvance($raceId) {
		$advance = $this->repository->getAdvance($this->id, $raceId);
		if ($advance) {
			return "Ano";
		} 
		return "Ne";
	}
	
	/**
	 * Vrátí, zda je hlídka potvrzená k účasti v závodě
	 * 
	 * @param int $raceId
	 * @return boolean
	 */
	public function isConfirmed($raceId) {
		return $this->repository->isConfirmed($this->id, $raceId);
	}	
	
	/**
	 * Přidá člena do hlídky, pokud je účasníkem al. jednoho ze závodů hlídky a 
	 * není již členem jiné hlídky
	 * 
	 * @param Person $member
	 * @throws LogicException
	 */
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
	 * @return Person[]
	 */
	public function getMembers($race = null) {		
		if (empty($this->members)) {			
			$this->members = $this->repository->getMembers($this->id);
		}		
		if (is_null($race)) {
			return $this->members;
		}
		
		$members = array();
		foreach ($this->members as $member) {			
			if (in_array($race->id, $member->races)) {
				$members[] = $member;
			}
		}
		return $members;		
	}

	/**
	 * Vrátí, zda je hlídka účastníkem závodu
	 * 
	 * @param Race $raceId
	 * @return boolean
	 */
	public function isInRace($raceId) {
		$this->getRaces();
		foreach ($this->races as $race) {
			if($race->id == $raceId) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	/**
	 * Vrátí, zda je hlídka účastníkem jednoho ze zadaných závodů
	 * 
	 * @param Race[] $raceArray
	 * @return boolean
	 */
	public function isInRaces($raceArray) {
		$this->getRaces();
		foreach ($this->races as $race) {
			if(in_array($race->id, $raceArray)) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	public function getNonCompetitiveReason() {
		$this->getCategory();
		return $this->nonCompetitiveReason;
	}
	
	/**
	 * Smaže členy hlídky
	 * 
	 * @param int $raceId ID závodu, na který je omezen výběr pro smazání
	 */
	public function deleteAllMembers($raceId = NULL) {
		if ($this->repository->deleteAllMembers($this->id, $raceId)) {
			$this->members = array();
		}
	}
	
	/**
	 * Uloží změny ve hlídce
	 */
	public function save() {
		$this->repository->save($this);
	}
	
	/**
	 * Nastaví kategorii napevno
	 */
	public function fixCategory() {
		$this->repository->fixCategory($this->id, $this->getCategory());
	}
	
	/**
	 * Vykoná postup hlídky
	 * 
	 * Přidá do navazujícího závodu hlídku i všechny její členy, které lze poté upravit
	 * 
	 * @param Race $race
	 */
	public function processAdvance(Race $race) {
		$this->repository->processAdvance($this, $race);
	}
	
	/**
	 * Vrátí token pro potvrzení přihlášení hlídky na závod vedoucím oddílu
	 * 
	 * @param int $raceId ID závodu, kam se hlídka přihlašuje
	 * @return $string
	 */
	public function getToken($raceId) {
		return $this->repository->getToken($this->id, $raceId);
	}
	
	public function setToken($raceId, $token) {
		$this->repository->setToken($this->id, $raceId, $token);
	}
	
	/**
	 * Potvrdí účast hlídky v závodě
	 * 
	 * @param string $token
	 * @return boolean
	 */
	public function confirm($token) {
		return $this->repository->confirm($this->id, $token);
	}
	
	/**
	 * Vrátí název ročníku, kterého se hlídka účastnila
	 * 
	 * @return string
	 */
	public function getSeasonName() {
		if ($this->getRaces()) {
			return $this->repository->getSeasonName($this->races[0]->season);
		}
		return NULL;
	}
	
	/**
	 * Ověří, zda přihlášený uživatel může upravovat hlídku
	 * 
	 * @param Nette\Security\LoggedUser $user
	 * @return boolean
	 */
	public function canEdit(Nette\Security\LoggedUser $user) {
		//uživatel je administrátorem
		if ($user->isInRole('admin')) {
			return TRUE;
		}
		//nebo je činovníkem střediska či oddílu hlídky
		if (($this->troop == $user->getUnit() || $this->group == $user->getUnit())
				&& $user->isOfficial()) {
			return TRUE;
		}
		//nebo je editorem závodu, kterého se hlídka účastní
		foreach ($user->identity->data["races"] as $raceId) {
			if ($user->isInRole('raceManager') && $this->isInRace($raceId)) {
				return TRUE;
			}
		}
		//nebo hlídku založil
		$this->getAuthor();
		if ($this->author->id == $user->id) {
			return TRUE;
		}
		// v ostatních případech nemůže upravovat
		return FALSE;
		
	}
}
