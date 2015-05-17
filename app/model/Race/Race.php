<?php

/**
 * Description of Race
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Race extends \Nette\Object {
	
	/**
	 *
	 * @var RaceRepository
	 */
	private $repository;
	
	/**
	 *
	 * @var int
	 */
	private $id;
	
	/**
	 *
	 * @var int
	 */
	private $season;
	
	/**
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * Rozšiřující informace k závodu, html formátované
	 * 
	 * @var string
	 */
	private $description;
	
	/**
	 *
	 * @var User
	 */
	private $author;
	
	/**
	 * Informace o druhu kola (základní, krajské, ...)
	 * 
	 * @var \Nette\Database\ActiveRow
	 */
	private $round;
	
	/**
	 * Inforamce o kraji
	 * 
	 * @var \Nette\Database\ActiveRow 
	 */
	private $region;
	
	/**
	 * Postupové kolo
	 * 
	 * @var \Race
	 */
	private $advance;
	
	/**
	 * Organizující jednotka
	 * 
	 * @var \Unit
	 */
	private $organizer;
	
	/**
	 * Termín konání závodu
	 * 
	 * @var \DateTime
	 */
	private $date;
	
	/**
	 * Místo konání závodu
	 * 
	 * @var string
	 */
	private $place;	
	
	/**
	 * Velitel závodu
	 * 
	 * @var string
	 */
	private $commander;
	
	/**
	 * Hlavní rozhodčí
	 * 
	 * @var string
	 */
	private $referee;
	
	/**
	 * 
	 * @var string
	 */
	private $telephone;
	
	/**
	 * 
	 * @var string
	 */
	private $email;
	
	/**
	 * 
	 * @var string
	 */
	private $web;
	
	/**
	 * Max. počet soutěžících hlídek
	 * 
	 * @var int
	 */
	private $capacity;
	
	/**
	 * Nejzašší termín pro přihlášení k závodu
	 * 
	 * @var DateTime
	 */
	private $applicationDeadline;
	
	/**
	 *
	 * @var \Nette\Database\ActiveRow 
	 */
	private $key;
	
	/**
	 * Krátký popis, pro koho je závod určen
	 * 
	 * @var string
	 */
	private $targetGroup;
	
	/**
	 * Informace o rozpětí členů
	 * 
	 * @var \Nette\Database\ActiveRow
	 */
	private $membersRange;
	
	/**
	 * 
	 * @var string
	 */
	private $commanderEmail;
	
	/**
	 * 
	 * @var string
	 */
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
		return $this->author;
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
		$this->getMembersRange();
		return $this->membersRange->min;
	}
	
	public function getMaxRunner() {
		$this->getMembersRange();
		return $this->membersRange->min;
	}

	public function getNumWatchs($category = NULL) {		
		return $this->repository->getNumWatchs($this->id, $category);
	}
	
	/**
	 * Vrátí počet postupujících hlídek v dané kategorii
	 *  
	 * @param string $category Název kategorie
	 * @return int Počet postupujících hlídek
	 */
	public function getNumAdvance($category) {
		
		// z celostátního kola se nepostupuje
		if ($this->getRound()->short == 'C') {
			return 0;
		//z krajského kola postupuje pouze hlídka
		} else if ($this->getRound()->short == 'K') {
			return 1;
		//jinak je postup určen postupovým klíčem v závislosti na počtu hlídek
		} else {			
			$numWatchs = $this->getNumWatchs($category);
			// klíč je omezen 20 hlídkami, pokud je jich více, platí pravidla pro 20 hlídek
			if ($numWatchs > 20) {
				$numWatchs = 20;
			}
			if ($numWatchs <= 1) {
				return 0;
			}
			$key = $this->getKey();
			return $key["{$numWatchs}"];			
		}
	}
	
	/**
	 * Ověří zda uživatel může editovat závod
	 * 
	 * @param int $userId ID přihlášeného uživatele
	 * @return boolean
	 */
	public function canEdit($userId) {		
		foreach ($this->getEditors() as $editor) {
			if ($userId == $editor->id) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	/**
	 * Ověří, zda se na závod lze přihlásit
	 * 
	 * Přihlašoval lze na základní kola aktuálně zvoleného ročníku,
	 * která mají před termínem pro přihlašování a nebyla u nich naplněna kapacita
	 * 
	 * @param type $season
	 * @return boolean
	 */
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
	
	/**
	 * Vrátí vygenerovaný token pro potvrzení výsledků
	 * 
	 * @return string Ověřovací token
	 */
	public function getToken() {
		return $this->repository->getToken($this->id);
	}
	
	public function setToken($token) {
		$this->repository->setToken($this->id, $token);
	}
	
	/**
	 * Provede potvrzení výsledků, pokud je platný token
	 * 
	 * @param string $token
	 * @return boolean
	 */
	public function confirm($token) {
		return $this->repository->confirm($this->id, $token);
	}
	
	/**
	 * Vymaže postupující hlídky z navazujících kol
	 * 
	 * Při úpravě výsledků
	 */
	public function deleteAdvancedWatchs() {
		$this->repository->deleteAdvancedWatchs($this->id, $this->getAdvance()->id);
	}
	
}
