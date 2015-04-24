<?php

namespace App\Forms;

use Nette,
	Nette\Application\UI\Form,
	Nette\Security\User;

/**
 * Description of MembersFormFactory
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class MembersFormFactory extends BaseFormFactory {
	
	private $watchRepository;
	private $raceRepository;
	private $unitRepository;
	private $user;
	private $season;
	private $race;
	private $id;
	private $troop;
	private $session;
	private $personRepository;
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param \Nette\Database\Context $database
	 * @param \WatchRepository $watchRepository
	 * @param \RaceRepository $raceRepository
	 * @param \UnitRepository $unitRepository
	 * @param \Nette\Security\LoggedUser $loggedUser
	 * @param \Nette\Http\Session $session
	 * @param \PersonRepository $personRepository
	 */
	public function __construct(\Skautis\Skautis $skautIS, \Nette\Database\Context $database, \WatchRepository $watchRepository, \RaceRepository $raceRepository, \UnitRepository $unitRepository, \Nette\Security\LoggedUser $loggedUser, Nette\Http\Session $session, \PersonRepository $personRepository) {
		parent::__construct($skautIS, $database);
		$this->watchRepository = $watchRepository;
		$this->raceRepository = $raceRepository;
		$this->unitRepository = $unitRepository;
		$this->personRepository = $personRepository;
		$this->user = $loggedUser;		
		$this->session = $session;
	}
	
	public function setId($id) {
		$this->id = $id;
	}		
	
	public function setTroop($troop) {
		$this->troop = $troop;
	}

	/**
	 * 
	 * @return Form
	 */
	public function create() {
		
		$form = new Form;	
		if (!isset($this->id)) {
			//var_dump($this->session->getSection('watch')->basic);exit;
			$troopId = $this->session->getSection('watch')->basic["troop"];
			$this->troop = $this->unitRepository->getUnit($troopId);
		}
		
		$form->addSelect("units", "Zobraz jednotky", $this->loadGroups())
				->setPrompt("-- vyber jednotku --");
		$form->addButton("loadMembers", "Načíst členy vybrané jednotky");
		$form->addContainer('members');
		$form->onSuccess[] = array($this, 'formSucceeded');
		$form->addSubmit('preview', "<< Předchozí krok");
		$form->addSubmit('addMembers', "Přidat vybrané členy");
		$form->addSubmit('send', "Další krok >>");
		
				
		return $form;
	}
	
	public function formSucceeded(Form $form) {		
		$values = $form->getHttpData();		
		$section = $this->session->getSection('watch');
		if (isset($values["members"])) {
			foreach ($values["members"] as $member) {		
				$section->members[$member] = $this->personRepository->getPerson($member)->displayName;
				$section->roles[$member] = $values["roles"][$member];
				$section->units[$member] = $values["units"];
			}
		}
		
		
	}
	
	public function loadGroups() {
		if (is_null($this->troop)) {
			throw new Nette\InvalidStateException("Hlídka musí mít nastavené středisko");
		}
		$result = $this->troop->getSubordinateUnits();
		$groups = array();
		foreach ($result as $unit) {
			$groups[$unit->id] = "$unit->unitType $unit->displayName";
			foreach ($unit->getSubordinateUnits() as $smallGroup) {
				$groups[$smallGroup->id] = " - $smallGroup->unitType $smallGroup->displayName";
			}
		}
		return $groups;
	}
}
