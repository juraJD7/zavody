<?php

namespace App\Presenters;

use Nette,
	App\Model,
	Nette\Application\UI\Form;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	/**
	 *
	 * @var \Nette\Database\Context
	 * @inject
	 */
	public $database;
	
	/**
	 *
	 * @var \Skautis\Skautis
	 * @inject
	 */
	public $skautIS;
	
	/**
	 *
	 * @var \UserRepository
	 * @inject
	 */
	public $userRepository;
	
	/**
	 *
	 * @var \UnitRepository
	 * @inject
	 */
	public $unitRepository;
	
	protected $season;
		
	public function startup() {
		parent::startup();			
			
		if($this->skautIS->getUser()->isLoggedIn()) {			
			$this->template->url = $this->link('Homepage:logout');
			$this->template->text = "Odhlásit se";
			$id = $this->skautIS->getUser()->getLoginId();
			$this->skautIS->usr->LoginUpdateRefresh(array("ID" => $id));
		} else {
			$this->user->logout(TRUE);
			$this->template->url = $this->skautIS->getLoginUrl($this->link('//this'));
			$this->template->text = "Přihlásit se";			
		}			
		$this->setSeason();	
		$this->template->season = $this->season;
		
	}
	
	/**
	 * @return \Form
	 */
	public function createComponentSeasonForm() {
		
		$form = new Form;
		
		$table = $this->database->table('season');
		$seasons = array();
		foreach ($table as $row) {
			$race = $this->database->table('competition')->get($row->competition)->name;
			$seasons[$row->id] = "$race $row->year";
		}
		
		$form->addSelect('season', "Aktuální závod:", $seasons)
				->setDefaultValue($this->season)
				->setAttribute('class','form-control');
		$form->onSuccess[] = array($this, 'seasonFormSucceeded');
		return $form;
	}
	
	public function seasonFormSucceeded($form, $values) {
		$this->loginRefresh();
	}
	
	protected function loginRefresh() {
		if ($this->user->isLoggedIn()) {
			$userDetail = $this->skautIS->user->UserDetail();		
			$this->user->login($userDetail);
			$this->user->setExpiration('30 minutes', TRUE);		
			$this->userRepository->getUser($userDetail->ID); // aktualizuje udaje o uživateli
		}
	}

	/**
	 * @return \Form
	 */
	public function createComponentRoleForm() {
		
		$form = new Form;
		
		$roles = $this->user->getAllSkautISRoles();
		$list = array();
		foreach ($roles as $role) {
			$list[$role->ID] = $role->DisplayName;
		}
		
		$form->addSelect('skautISRoles', "Role ve SkautISu:", $list)
				->setDefaultValue($this->user->getSkautISRole())
				->setAttribute('class', 'form-control');	
		return $form;
	}
	
	private function setSeason() {
		$defaultSeason = $this->database->table('setting')->get('season')->value;
		if (!isset($_COOKIE['season'])) {				
			setcookie("season", $defaultSeason, time() + 7776000, '/'); // 3 months
			$this->season = $defaultSeason;
		} else {			
			$season = $this->database->table('season')->get($_COOKIE['season']);			
			if (!$season) {
				setcookie("season", $defaultSeason, time() + 7776000, '/'); // 3 months
				$this->season = $defaultSeason;
			} else {
				setcookie("season", $season->id, time() + 7776000, '/'); // 3 months
				$this->season = $season->id;
			}	
		}
	}
	
	public function handleChangeISRole() {
		$roleId = $this->getHttpRequest()->getPost('roleId');		
		$this->user->updateSkautISRole($roleId);
		$this->redirect('this');
	}
}
