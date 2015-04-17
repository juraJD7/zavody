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
	
	private $season;
	private $user;
	private $id;
	private $editors = array();
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param \Nette\Database\Context $database
	 * @param \RaceRepository $raceRepository
	 * @param \Nette\Security\LoggedUser $loggedUser
	 */
	public function __construct(\Skautis\Skautis $skautIS, \Nette\Database\Context $database, \RaceRepository $raceRepository, \Nette\Security\LoggedUser $loggedUser) {
		parent::__construct($skautIS, $database);
		$this->raceRepository = $raceRepository;
		$this->user = $loggedUser;
	}
	
	public function setSeason($season) {
		$this->season = $season;
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
		$form = new Form();	
		
		$form->addGroup('Základní nastavení');
		$form->addText("name", "Název kola:");//->setRequired();		
		$form->addSelect("round", "Druh kola:", $this->loadRounds() )
				->setPrompt("-- vyber kolo --")
				->setRequired();		
		$form->addSelect("key", "Postupový klíč:", $this->loadKeys() )
				->setPrompt("-- vyber klíč --");
		$form->addSelect("region", "Kraj:", $this->loadRegions() )
				->setPrompt("-- vyber kraj --")
				->setRequired();		
		$form->addSelect("members_range", "Počet členů ve družině:", $this->loadMembersRange() )
				->setPrompt("-- vyber rozsah --")
				->setRequired();
		$form->addText("capacity", "Max. počet družin:")
				->setType('number')
				->addRule(Form::INTEGER, 'Kapacita družin musí být číslo')
				->addRule(Form::MIN, 'Kapacita nemůže být záporná', 0);//->setRequired();
		$form->addText("application_deadline", "Uzávěrka přihlášek:")
				->setType("date");
			//	->setOption("description", "ve formátu rrrr-mm-dd nebo vyberte datum ze zobrazeného kalendáře (Chrome, FF)");//->setRequired();
		$form->addGroup('Pořadatel');
		$form->addSelect("organizer", "Pořádající jednotka:", $this->loadUnits())
				->setRequired();
		$form->addText("commander", "Velitel závodu:");//->setRequired();
		$form->addText("referee", "Hlavní rozhodčí:");//->setRequired();
		$form->addSelect("editors_input", "Editoři závodu:", $this->loadUsers())
				->setPrompt('-- vybrat další editory z ISu --')
				->setAttribute('class', 'js-example-basic-single')
				->setOption("descriprion", " * zadej login(y) lidí z ISu, kteří mohou upravovat parametry závodu");	
		$form->addGroup('Datum a Místo');
		$form->addText("date","Termín závodu:")
				->setType("date");
				//->setOption("description", "ve formátu rrrr-mm-dd nebo 
				//vyberte datum ze zobrazeného kalendáře (Chrome, FF)");//->setRequired();
		$form->addText("place", "Místo:");//->setRequired();
		$form->addText("gps_x", "GPS X:");
				//->setOption("description", "Odkaz na google mapy");//->setRequired();
		$form->addText("gps_y", "GPS Y:");//->setRequired();
		$form->addGroup('Kontakt');
		$form->addText("telephone", "Telefon:");
		$form->addText("email", "Kontaktní mail:")
				->setType("email");//->setRequired();
		$form->addText("web", "Web:");
		$form->addGroup("Další nastavení");
		$form->addText("target_group", "Cílová skupina (popis):");//->setRequired();	
		//$form->addTextArea("description", "Další informace", "60", "10");
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
				->where('round', $values->round)
				->where('id !=', $this->id);
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
		$post = $form->getHttpData();
		$values["author"] = $this->user->id;
		$values["season"] = $this->season;
		unset($values["editors_input"]);
		$values["advance"] = $this->setAdvanceRace($values->region, $values->round);
		if (isset($this->id)) {			
			$row = $this->database->table('race')
					->where('id', $this->id)
					->update($values);
			$race = $this->database->table('race')
					->get($this->id);
		} else {			
			$race = $this->database->table('race')
				->insert($values);
		}		
		$this->setSubordinateRaces($values->region, $values->round, $race->id);
		if (isset($post['editors'])) {
			$this->setNewEditors($post['editors'], $race->id);
		}
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
		$result = $this->database->table('key');
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
		$subordinate = array();		
		$myUnit = array($this->user->unit->id => "-- Moje jednotka (" . $this->user->unit->displayName . ") --");
		
		foreach ($this->user->unit->subordinateUnits as $unit) {
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
			$detail = $this->skautIS->usr->UserDetail(array("ID" => $isUser->ID));
			$users[$detail->ID] = "$detail->UserName ($detail->Person)";
		}
		return $users;
	}
}
