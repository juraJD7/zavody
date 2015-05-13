<?php

namespace App\Forms;

use Nette,
	Nette\Application\UI\Form,
	Nette\Security\User;

/**
 * Description of RaceFormFactory
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class RaceFormFactory extends BaseFormFactory {
	
	private $raceRepository;
	private $userRepository;
	private $unitRepository;
	
	private $user;
	private $id;
	private $editors = array();
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param \Nette\Database\Context $database
	 * @param \RaceRepository $raceRepository
	 * @param \Nette\Security\LoggedUser $loggedUser
	 * @param \UserRepository $userRepository
	 * @param \UnitRepository $unitRepository
	 */
	public function __construct(\Skautis\Skautis $skautIS, \Nette\Database\Context $database, \RaceRepository $raceRepository, \Nette\Security\LoggedUser $loggedUser, \UserRepository $userRepository, \UnitRepository $unitRepository) {
		parent::__construct($skautIS, $database);
		$this->raceRepository = $raceRepository;
		$this->userRepository = $userRepository;
		$this->unitRepository = $unitRepository;
		$this->user = $loggedUser;
	}	
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function setEditors($editors) {
		$this->editors = $editors;
	}
	
	/**
	 * @return Form
	 */
	public function create()
	{
		$form = new Form;
		$form->addGroup('Základní nastavení');
		$form->addText("name", "Název kola* :")
				->setRequired("Je nutné vyplnit název kola.");		
		$form->addSelect("round", "Druh kola* :", $this->loadRounds() )
				->setPrompt("-- vyber kolo --")
				->setRequired();		
		$form->addSelect("key", "Postupový klíč:", $this->loadKeys() )
				->setPrompt("-- vyber klíč --");
		$form->addSelect("region", "Kraj* :", $this->loadRegions() )
				->setPrompt("-- vyber kraj --")
				->setRequired("Je nutné vybrat kraj závodu");		
		$form->addSelect("members_range", "Počet členů ve družině* :", $this->loadMembersRange() )
				->setPrompt("-- vyber rozsah --")
				->setRequired("Je nutné vybrat povolený rozsah členů v závodě");
		$form->addText("capacity", "Max. počet družin* :")
				->setType('number')
				->addRule(Form::INTEGER, 'Kapacita družin musí být číslo')
				->addRule(Form::MIN, 'Kapacita nemůže být záporná', 0)
				->setRequired("Je nutné zadat max. počet přihlášených hlídek (součet za všechny kategorie)");
		$form->addText("application_deadline", "Uzávěrka přihlášek* :")
				->setType("date")
				->setRequired("Je nutné zadat deadline pro přihlášky.");
		$form->addGroup('Pořadatel');
		$form->addSelect("organizer", "Pořádající jednotka* :", $this->loadUnits())
				->setAttribute('class', 'js-example-basic-single')
				->setRequired();
		$form->addText("commander", "Velitel závodu* :")
				->setRequired("Zadejte velitele závodu");
		$form->addText("commander_email", "E-mail - velitel* :")
				->setType("email")
				->addRule(Form::EMAIL, 'E-mailová adresa není platná')
				->setRequired("Zadejte email na velitele závodu");
		$form->addText("referee", "Hlavní rozhodčí* :")
				->setRequired("Zadejte hl. rozhodčího závodu");
		$form->addText("referee_email", "E-mail - rozhodčí* :")
				->setType("email")
				->addRule(Form::EMAIL, 'E-mailová adresa není platná')
				->setRequired();
		$form->addMultiSelect("editors_input", "Editoři závodu:", $this->loadUsers())
				->setAttribute('class', 'js-example-basic-multiple');	
		$form->addGroup('Datum a Místo');
		$form->addText("date","Termín závodu* :")
				->setType("date")				
				->setRequired("Je nutné zadat termín konání závodu.");
		$form->addText("place", "Místo* :")
				->setRequired("Je nutné zadat místo konání.");		
		$form->addGroup('Kontakt');
		$form->addText("telephone", "Telefon:");
		$form->addText("email", "Kontaktní mail* :")
				->addRule(Form::EMAIL, 'E-mailová adresa není platná')
				->setType("email")
				->setRequired("Je nutné zadat email na organizátora.");
		$form->addText("web", "Web:")
				->addCondition(Form::FILLED)
				->addRule(Form::URL, 'Webová adresa není platná.');
		$form->addGroup("Další nastavení");
		$form->addText("target_group", "Cílová skupina (popis):");				
		$form->addTextArea("description", "Další informace", "60", "10")
				->setAttribute('class', 'mceEditor');
		$form->addSubmit("send","Odeslat");
		
		$renderer = $form->getRenderer();
		$this->addBootstrapRendering($renderer, $form);
		
		$form->onValidate[] = array($this, 'validateRounds');
		$form->onSuccess[] = array($this, 'formSucceeded');
		return $form;
	}
	
	/**
	 * Validace formuláře
	 * 
	 * @param type $form
	 */
	public function validateRounds($form) {		
		$values = $form->getValues();
		
		$races = $this->database->table('race')
				->where('season', $this->season)
				->where('round', $values->round);
		if (!is_null($this->id)) {
			$races = $races->where('id !=', $this->id);
		}
		if($values->round == "C" && $races->count() > 0) {
			$form->addError('Celostátní kolo již existuje.');
		}
		if($values->round == "K" && $races->where('region', $values->region)->count() > 0) {
			$region = $this->database->table('region')->get($values->region);
			$form->addError("Krajské kolo v kraji $region->name již existuje.");
		}
	}
	
	public function formSucceeded(Form $form, $values)
	{				
		
		$editors = $values->editors_input;
		unset($values->editors_input);
		if (is_null($this->id)) {
			$values["author"] = $this->user->id;
			$values["season"] = $this->season;			
		}
		$organizer = $this->unitRepository->getUnit($values->organizer);
		$organizer->save();
		if (substr($values->web, 0, 4) != "http") {
			$values->web = "http://" . $values->web;
		}		
		$values["advance"] = $this->setAdvanceRace($values->region, $values->round);
		if (isset($this->id)) {			
			$this->database->table('race')
					->where('id', $this->id)
					->update($values);
			$raceId = $this->id;
		} else {			
			$race = $this->database->table('race')
				->insert($values);
			$raceId = $race->id;
		}		
		$this->setSubordinateRaces($values->region, $values->round, $raceId);		
		$this->setNewEditors($editors, $raceId);
	}
	
	private function loadRounds() {
		$result = $this->database->table('round');
		$rounds = array();
		foreach ($result as $row) {
			$row = $result->fetch();
			$rounds[$row->short] = $row->name;
		}
		return $rounds;
	}
	
	private function loadKeys() {
		$result = $this->database->table('advance_key');
		$keys = array();
		foreach ($result as $row) {
			$row = $result->fetch();
			$keys[$row->id] = "Klíč č. " . $row->id . ": " . $row->description;
		}
		return $keys;
	}
	
	private function loadRegions() {
		$result = $this->database->table('region');
		$regions = array();
		foreach ($result as $row) {
			$row = $result->fetch();
			$regions[$row->id] = $row->name;
		}
		return $regions;
	}
	
	private function loadMembersRange() {
		$result = $this->database->table('members_range');
		$ranges = array();
		foreach ($result as $row) {
			$row = $result->fetch();
			$ranges[$row->id] = $row->name;
		}
		return $ranges;
	}
	
	public function loadUnits() {
		$types = array("ustredi", "kraj", "okres", "stredisko");		
		if (in_array($this->user->unit->unitType, $types)) {
			$myUnit = array($this->user->unit->id => "-- Moje jednotka (" . $this->user->unit->displayName . ") --");
		} else {
			$myUnit = array();
		}
		$subordinate = array();
		foreach ($this->unitRepository->getUnits($types) as $unit) {
			$subordinate[$unit->id] = $unit->displayName;
		}
		$units = $myUnit + $subordinate;
		return $units;
	}
	
	private function setSubordinateRaces($region, $round, $id) {		
		if($round == "C") {
			$this->database->table('race')
				->where('round', 'K')
				->where('season', $this->season)
				->update(array("advance" => $id));			
		} 
		if($round == "K") {
			$this->database->table('race')
				->where('round', 'Z')
				->where('region', $region)
				->where('season', $this->season)
				->update(array("advance" => $id));			
		}
	}

	private function setAdvanceRace($region, $round) {
		if($round == "K") {
			$race = $this->database->table('race')
					->where('round','C')
					->where('season',$this->season);			
		}
		if($round == "Z") {
			$race = $this->database->table('race')
					->where('round','K')
					->where('region',$region)
					->where('season',$this->season);			
		}
		
		if(!isset($race) || $race->count() == 0) {
			return null;
		}		
		return $race->fetch()->id;
	}
	
	public function setNewEditors($editors, $race) {
		$this->database->table('editor_race')
				->where('race_id', $race)
				->delete();
		foreach ($editors as $editor) {
			$this->userRepository->getUser($editor); // uložení editora, popř. aktualizace údajů z ISu
			$this->database->table('editor_race')
				->insert(array(
					"user_id" => $editor,
					"race_id" => $race
				));
		}
		$this->database->table('editor_race')->insert(array(
			"user_id" => $this->user->id,
			"race_id" => $race
		));
	}

	public function loadUsers() {
		$isUsers = $this->skautIS->usr->UserAll();
		$users = array();
		foreach ($isUsers as $isUser) {			
			$users[$isUser->ID] = "$isUser->UserName ($isUser->DisplayName)";
		}
		return $users;
	}
}
