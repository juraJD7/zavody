<?php

/**
 * Description of WatchDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class WatchDbMapper extends BaseDbMapper {		
	
	private $personRepositoryFactory;
	
	/**
	 * 
	 * @param \Nette\Database\Context $database
	 * @param \UserRepository $userRepository
	 * @param \UnitRepository $unitRepository
	 * @param $personRepositoryFactory
	 */
	public function __construct(\Nette\Database\Context $database, \UserRepository $userRepository, \UnitRepository $unitRepository, $personRepositoryFactory) {
		parent::__construct($database, $userRepository, $unitRepository);		
		$this->personRepositoryFactory = $personRepositoryFactory;
	}	
	
	/**
	 * 
	 * @return PersonRepository
	 */
	private function getPersonRepository() {
		return call_user_func($this->personRepositoryFactory);
	}

	/**
	 * Vrátí hlídku
	 * 
	 * @param int $id ID hlídky
	 * @param \WatchRepository $repository
	 * @return \Watch
	 * @throws Race\DbNotStoredException
	 */
	public function getWatch($id, WatchRepository $repository) {
		$row = $this->database->table('watch')->get($id);
		if(!$row) {
			throw new Race\DbNotStoredException("Hlídka $id neexistuje");
		}
		return $this->loadFromActiveRow($row, $repository);
	}
	
	/**
	 * Vrátí všechny potvrzené hlídky závodu
	 * 
	 * @param int $raceId
	 * @param WatchRepository $repository
	 * @return Watch[]
	 */
	public function getWatchs($raceId, WatchRepository $repository) {
		$result = $this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('confirmed', TRUE)				
				->order('order ASC');
		$watchs = array();
		foreach ($result as $row) {
			$watchs[] = $this->getWatch($row->watch_id, $repository);
		}
		return $watchs;
	}


	/**
	 * Vytvoří hlídku z řádku databáze
	 * 
	 * @param Nette\Database\Table\ActiveRow $row
	 * @return \Watch
	 */
	public function loadFromActiveRow(Nette\Database\Table\ActiveRow $row, WatchRepository $repository) {
		$watch = new Watch($row->id);
		$watch->repository = $repository;
		$watch->name = $row->name;
		$watch->town = $row->town;
		$watch->emailLeader = $row->email_leader;
		$watch->emailGuide = $row->email_guide;
		$watch->category = $row->category;
		$watch->getRaces();
		$watch->getMembers();		
		return $watch;
	}
	
	/**
	 * Vrátí uživatele, který hlídku založil
	 * 
	 * @param type $id
	 * @return User
	 */
	public function getAuthor($id) {
		$authorId = $this->database->table('watch')->get($id)->author;
		return $this->userRepository->getUser($authorId);
	}
	
	/**
	 * Vrátí oddíl nebo středisko hlídky podle typu
	 * 
	 * @param int $id ID hlídky
	 * @param string $type Typ dotazované jednotky
	 * @return Unit
	 */
	public function getUnit($id, $type) {
		$unitId = $this->database->table('watch')->get($id)->$type;
		return $this->unitRepository->getUnit($unitId);
	}
	
	/**
	 * Vrátí počet bodů hlídky v zadaném závodě
	 * 
	 * @param int $watchId
	 * @param int $raceId
	 * @return int
	 */
	public function getPoints($watchId, $raceId) {
		return $this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('watch_id', $watchId)
				->fetch()
				->points;
	}
	
	/**
	 * Vrátí poznámku k hodnocení hlídky v zadaném závodě
	 * 
	 * @param int $watchId
	 * @param int $raceId
	 * @return string
	 */
	public function getNote($watchId, $raceId) {
		return $this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('watch_id', $watchId)
				->fetch()
				->note;
	}
	
	/**
	 * Vrátí informaci o postupu hlídky ze závodu
	 * 
	 * @param int $watchId
	 * @param int $raceId
	 * @return boolean | NULL 1 v případě postupu, NULL v případě nepostup, 0 v případě pevně nastaveného nepostupu
	 * i na postupové pozici
	 */
	public function getAdvance($watchId, $raceId) {
		return $this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('watch_id', $watchId)
				->fetch()
				->advance;
	}
	
	/**
	 * Vrátí pořadí hlídky v závodě
	 * 
	 * @param int $watchId
	 * @param int $raceId
	 * @return int
	 */
	public function getOrder($watchId, $raceId) {
		return $this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('watch_id', $watchId)
				->fetch()
				->order;
	}
	
	/**
	 * Vrátí, zda je účast hlídky v závodě potvrzená
	 * 
	 * @param int $watchId
	 * @param int $raceId
	 * @return boolean
	 */
	public function isConfirmed($watchId, $raceId) {
		$confirmed = $this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('watch_id', $watchId)
				->fetch()
				->confirmed;
		if ($confirmed) {
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Provede uložení hlídky i se členy
	 * 
	 * @param Watch $watch
	 * @return int ID uložené hlídky
	 * @throws DbSaveException
	 */
	public function save(Watch $watch) {
		try {
			$watchId = $this->saveWatch($watch);
			// ucastnici
			$this->saveMembers($watch, $watchId);
		} catch (Exception $ex) {
			throw new DbSaveException("Hlídku se nepodařilo uložit do databáze", $ex);
		}		
		return $watchId;
	}
	
	/**
	 * Uloží informace o hlídce do databáze
	 * 
	 * @param Watch $watch
	 * @return int ID uložené hlídky
	 */
	private function saveWatch(Watch $watch) {
		$data = array(
			"name" =>  $watch->name,
			"author" => $watch->author->id,
			"group" => $watch->group->id,
			"troop" => $watch->troop->id,
			"town" => $watch->town,
			"email_leader" => $watch->emailLeader,
			"email_guide" => $watch->emailGuide
		);	
		//uložení jednotek do databáze
		$group = $this->unitRepository->getUnit($watch->group->id);
		$group->save();
		$troop = $this->unitRepository->getUnit($watch->troop->id);
		$troop->save();
		//aktualizace v případě editace hlídky
		if (!is_null($watch->id)) {			
			$rowWatch = $this->database->table('watch')
				->where('id', $watch->id)
				->update($data);
			$watchId = $watch->id;
		// nebo uložení nové hlídky
		} else {
			$rowWatch = $this->database->table('watch')
				->insert($data);
			$watchId = $rowWatch->id;
		}
		//uložení hlídky do závodů

		// race_watch - smažu staré vazby (v případě editace) a vložím nové -> aktualizace
		$this->database->table('race_watch')
				->where('watch_id', $watchId)
				->delete();
		foreach ($watch->races as $race) {
			$this->database->table('race_watch')
					->insert(array(
						"race_id" => $race->id,
						"watch_id" => $watchId
					));
		}
		return $watchId;
	}
	
	/**
	 * Uloží členy hlídky do databáze
	 * 
	 * @param Watch $watch
	 * @param int $watchId ID hlídky ukladádaných členů
	 */
	private function saveMembers(Watch $watch, $watchId) {
		// tabulka participant zustane, pouze se smazou vazby
		$watchMembers = $this->database->table('participant')
				->where('watch', $watchId);
		foreach ($watchMembers as $watchMember) {
			$this->database->table('participant_race')
					->where('participant_id', $watchMember->id)
					->delete();
		}
		//vytvoreni noveho participanta, je-li treba
		foreach ($watch->members as $participant) {	
			//pokus o nalezení účastníka
			$partRow = $this->database->table('participant')
					->where('person_id', $participant->personId)
					->where('watch', $watchId)
					->fetch();
			//uložení nového účastníka
			if (!$partRow) {
				$data = array(
					"person_id" => $participant->personId,
					"firstname" => $participant->firstName,
					"lastname" => $participant->lastName,
					"nickname" => $participant->nickName,
					"sex" => $participant->sexId,
					"birthday" => $participant->birthday,
					"unit" => $participant->unit->id,				
					"watch" => $watchId
				);
				$this->unitRepository->save($participant->unit);
				$partRow = $this->database->table('participant')
					->insert($data);
			}
			//porchází role účastníka napříč závody a ukládá je do databáze
			foreach ($participant->roles as $race => $role) {
				$this->database->table('participant_race')
					->insert(array(
						"participant_id" => $partRow->id,
						"race_id" => $race,
						"role_id" => $role
					));
			}			
		}
	}
	
	/**
	 * Vrátí závody hlídky
	 * 
	 * @param int $watchId
	 * @return Race[]
	 */
	public function getRaces($watchId) {
		$result =  $this->database->table('race_watch')
				->where('watch_id', $watchId);
		$raceIds = array();
		foreach ($result as $row) {
			$raceIds[] = $row->race_id;
		}
		return $raceIds;
	}
	
	/**
	 * Smaže členy hlídky
	 * 
	 * @param int $watchId
	 * @param int $raceId omezí výběr pro mazání na konkrétní závod
	 * @return int 1 pokud vše proběhlo v pořádku
	 */
	public function deleteAllMembers($watchId, $raceId = null) {
		$members = $this->database->table('participant')
				->where('watch', $watchId);
		foreach ($members as $member) {
			$this->database->table('participant_race')
					->where('participant_id', $member->id)
					->where('race_id', $raceId)
					->delete();
			if ($this->database->table('participant_race')
					->where('participant_id', $member->id)
					->count() == 0) {
				$member->delete();
			}
		}		
		return 1;
	}
	
	/**
	 * Ověří, zda je věk účastníka v souladu s nastavením závodu
	 * 
	 * @param int $personId
	 * @param int $roleId ID role závodníka
	 * @param Race $race
	 * @return boolean | string Vrací TRUE pokud je věk v pořádku, řetězec s odůvodněním v případě 
	 * nevyhovujícího věku
	 */
	private function validateAgeMember($personId, $roleId, $race) {
		//pro doprovod nejsou věková omezení
		if ($roleId == Person::TYPE_ESCORT) {			
			return TRUE;
		}
		$person = $this->getPersonRepository()->getPerson($personId);
		//ověří věk běžného účastníka
		if ($roleId == Person::TYPE_RUNNER ) {			
			$age = $person->birthday > $race->getRunnerAge();
			$message = "Pro osobu $person->displayName byl překročen věk závodníka. Zvolte jinou kategorii.";
		}
		//ověří věk rádce
		if ($roleId == Person::TYPE_GUIDE ) {			
			$age = $person->birthday > $race->getGuideAge();				
			$message = "Pro osobu $person->displayName byl překročen věk rádce. Starší osoby se musí uvést jako doprovod.";
		}
		
		if ($age) {
			return $age;
		} else {
			return $message;
		}
	}
	
	/**
	 * Ověří, zda není osoba členem jiné hlídky v aktuálním ročníku
	 * 
	 * @param int $personId
	 * @param int $roleId
	 * @param int $race
	 * @param int $watchId
	 * @return boolean |string TRUE  v případě, že je vše v pořádku, string s odůvodněním v případě, že
	 * osoba již je členem jiné hlídky v aktuálním ročníku
	 */
	private function validateMemberCollision($personId, $roleId, $race, $watchId) {
		//doprovod může být členem více hlídek, pro ostatní role probíhá kontrola
		$collisionRoles = array(\Person::TYPE_RUNNER, \Person::TYPE_GUIDE);
		if (!in_array($roleId, $collisionRoles)) {
			return TRUE;
		}
		//najde všechny výskyty osoby v databázi jako účastníky jednotlivých ročníků
		$participants = $this->database->table('participant')
				->where('person_id', $personId);
		foreach ($participants as $participant) {
			//závody, kterých se účastník účastní v některé z kolizních rolí
			$races = $this->database->table('participant_race')
					->where('participant_id', $participant->id)
					->where('role_id', $collisionRoles);
			foreach ($races as $raceId) {				
				$newRace = $this->database->table('race')->get($raceId->race_id);		
				// pokud je závod ve stejném ročníku, člena nelze do hlídky přidat
				if ($newRace->season == $race->season) {
					$person = $this->getPersonRepository()->getPerson($personId);
					if (is_null($watchId)) {						
						return "Osoba $person->displayName je již členem jiné hlídky";
					} else {
						//jedinou vyjmkou je, že se jedná o tu samou hlídku (při úpravě hlídky)
						//pokud však je hlídka jiná, opět nelze přidat
						if ($watchId != $participant->watch) {							
							return "Osoba $person->displayName je již členem jiné hlídky";
						}
					}				
				}
			}
		}
		return TRUE;
	}
	
	/**
	 * 
	 * @param int $personId ID osoby ze skautISu
	 * @param int $roleId ID role účastníka
	 * @param Race $race závod, kam se osoba přihlašuje
	 * @param int $watchId ID hlídky, kam se osoba přihlašuje
	 * @return boolean | string TRUE v případě úspěchu, v opačném případě string s odůvodněním
	 */
	public function validateMember($personId, $roleId, $race, $watchId = NULL) {		
		$validateAge = $this->validateAgeMember($personId, $roleId, $race);			
		if ($validateAge !== TRUE) {			
			return $validateAge;
		}
		return $this->validateMemberCollision($personId, $roleId, $race, $watchId);
	}
	
	/**
	 * Nastaví trvale kategorii hlídky, které poté nelze měnit
	 * 
	 * @param int $watchId
	 * @param string $category
	 */
	public function fixCategory($watchId, $category) {
		$this->database->table('watch')
			->where('id', $watchId)
			->update(array("category" => $category));
	}
	
	/**
	 * Provede postup hlídky ze zadaného kola
	 * 
	 * @param Watch $watch
	 * @param Race $race
	 */
	public function processAdvance(Watch $watch, Race $race) {
		$advanceRace = $race->getAdvance();	
		//pokud existuje navazující kolo
		if ($advanceRace) {			
			$rows = $this->database->table('participant_race')
					->where('race_id', $race->id);
			foreach ($rows as $row) {
				$participant = $this->database->table('participant')
						->where('id', $row->participant_id)
						->where('watch', $watch->id)->fetch();				
				if ($participant) {
					$this->database->table('participant_race')
							->insert(array(
								"participant_id" => $row->participant_id,
								"race_id" => $advanceRace->id,
								"role_id" => $row->role_id
							));
				}
			}
			$this->database->table('race_watch')
					->insert(array(
						"race_id" => $advanceRace->id,
						"watch_id" => $watch->id,
						"confirmed" => 1
					));
		} else {
			throw new LogicException("Nebyl nalezen navazující závod. Ze závodu nelze postoupit.");
		}
	}
	
	/**
	 * Vrátí ověřovací token pro potvrzení účasti hlídky
	 * 
	 * @param int $watchId
	 * @param int $raceId
	 * @return string
	 */
	public function getToken($watchId, $raceId) {
		return $this->database->table('race_watch')
				->where('watch_id', $watchId)
				->where('race_id', $raceId)
				->fetch()
				->token;
	}
	
	/**
	 * Nastaví ověřovací token pro potvrzení účasti hlídky
	 * 
	 * @param int $watchId
	 * @param int $raceId
	 * @param string $token
	 */
	public function setToken($watchId, $raceId, $token) {
		$this->database->table('race_watch')
				->where('watch_id', $watchId)
				->where('race_id', $raceId)
				->update(array("token" => $token));
	}
	
	/**
	 * Změní příznak hlídky na potvrzen
	 * 
	 * @param int $watchId
	 * @param String $token
	 * @return int Počet ovlivněných řádků
	 */
	public function confirm($watchId, $token) {
		return $this->database->table('race_watch')
				->where('watch_id', $watchId)
				->where('token', $token)
				->update(array(
					"confirmed" => 1
				));
	}
	
	/**
	 * Vrátí hlídky podle autora
	 * 
	 * @param WatchRepository $repository
	 * @param int $userId
	 * @return Watch[]
	 */
	public function getWatchsByAuthor(WatchRepository $repository, $userId) {
		$rows = $this->database->table('watch')
				->where('author', $userId);
		$watchs = array();
		foreach ($rows as $row) {
			//pouze ze závodů aktuálního ročníku
			if ($this->isInSeason($row->id)) {
				$watchs[$row->id] = $this->getWatch($row->id, $repository);			
			}
		}
		return $watchs;
	}
	
	/**
	 * Vrátí hlídky podle jednotky (oddíl, středisko hlídky)
	 * 
	 * @param WatchRepository $repository
	 * @param int $unitId
	 * @return Watch[]
	 */
	public function getWatchsByUnit(WatchRepository $repository, $unitId) {
		$rows = $this->database->table('watch')
				->where('group = ? OR troop = ?', $unitId, $unitId);
		$watchs = array();
		foreach ($rows as $row) {
			//pouze ze závodů aktuálního ročníku
			if ($this->isInSeason($row->id)) {
				$watchs[$row->id] = $this->getWatch($row->id, $repository);			
			}
		}
		return $watchs;		
	}
	
	/**
	 * Vrátí hlídky podle osoby
	 * 
	 * @param WatchRepository $repository
	 * @param int $personId
	 * @return Watch[]
	 */
	public function getWatchsByParticipant(WatchRepository $repository, $personId) {
		$rows = $this->database->table('participant')
				->where('person_id', $personId);
		$watchs = array();
		foreach ($rows as $row) {
			//pouze z aktuálního ročníku
			if ($this->isInSeason($row->watch)) {
				$watchs[$row->watch] = $this->getWatch($row->watch, $repository);
			}
		}
		return $watchs;
	}
	
	/**
	 * Vrátí, zda se hlídka účastní závodů z aktuálního ročníku
	 * 
	 * @param int $watchId
	 * @return boolean
	 */
	private function isInSeason($watchId) {
		$row = $this->database->table('race_watch')
				->where('watch_id', $watchId)
				->fetch();
		if ($row) {
			$race = $this->database->table('race')->get($row->race_id);
			if ($race->season == $this->season) {
				return TRUE;
			}
		}
		return FALSE;
	}


	/**
	 * Vrátí jméno ročníku
	 * 
	 * @param int $seasonId
	 * @return string
	 */
	public function getSeasonName($seasonId) {
		$season = $this->database->table('season')->get($seasonId);
		$competition = $this->database->table('competition')->get($season->competition)->short;
		return "$competition $season->year";
	}
	
	/**
	 * Smaže hlídku ze závodu
	 * 
	 * @param int $watchId
	 * @param int $raceId
	 * @return boolean TRUE pokud vše proběhlo v pořádku
	 */
	public function deleteWatch($watchId, $raceId) {
		$this->database->table('race_watch')
				->where('race_id', $raceId)
				->where('watch_id', $watchId)
				->delete();
		//počet závodů, kterých se po smazání hlídka účastní
		$row = $this->database->table('race_watch')
				->where('watch_id', $watchId)
				->count();
		//načtení všech členů hlídky
		$participants =	$this->database->table('participant')
					->where('watch', $watchId);	
		//smazání členů z konkrétního závodu
		foreach ($participants as $participant) {
			$this->database->table('participant_race')
					->where('race_id', $raceId)
					->where('participant_id', $participant->id)
					->delete();
			//pokud hlídka není členem žádného závodu, smaže se i záznam o účastníkovi
			if ($row == 0) {	
				$participant->delete();
			}
		}
		//pokud hlídka není členem žádného závodu, smaže i hlídku samotnou
		if ($row == 0) {
			$this->database->table('watch')
					->where('id', $watchId)
					->delete();
		}		
		return true;
	}
	
	/**
	 * Odhlásí hlídku z postupového závodu
	 * 
	 * Postupu se trvale zabrání nastavením hodnoty 0 v databázi
	 * 
	 * @param WatchRepository $repository
	 * @param Watch $watch
	 * @param Race $prevRace
	 */
	public function unsetAdvance(WatchRepository $repository, Watch $watch, Race $prevRace) {
		$row = $this->database->table('race_watch')
				->where('race_id', $prevRace->id)
				->where('watch_id', $watch->id);
		// zruší postup a napíše důvod nepostupu
		$row->update(array(
				"advance" => 0,
				"note" => "Hlídka se zřekla postupu"
			));
		//nalezení následujících hlídek v pořadí závodu
		$order = $row->fetch()->order;		
		$table = $this->database->table('race_watch')
				->where('advance', NULL)
				->where('order >', $order)
				->order('order ASC');
		// prochází hlídky podle pořadí a první hlídka, která je stejné kategorie
		// a nemá postup trvele nastavený na "nepostup", postoupí
		foreach ($table as $row) {
			$tmpWatch = $this->database->table('watch')->get($row->watch_id);
			if ($watch->category == $tmpWatch->category) {
				$row->update(array("advance" => TRUE));
				$advWatch = $repository->getWatch($row->watch_id);
				//provedení samotného postupu
				$advWatch->processAdvance($prevRace);
				break;
			}
		}
	}
}
