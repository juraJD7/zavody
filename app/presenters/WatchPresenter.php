<?php

namespace App\Presenters;

use Nette;

/**
 * Description of WatchPresenter
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class WatchPresenter extends BasePresenter {
	
	/**
	 *
	 * @var \RaceRepository
	 * @inject
	 */
	public $raceRepository;
	
	/**
	 *
	 * @var \WatchRepository
	 * @inject
	 */
	public $watchRepository;
	
	/**
	 *
	 * @var \PersonRepository
	 * @inject
	 */
	public $personRepository;
	
	/**
	 *
	 * @var \App\Forms\WatchFormFactory
	 * @inject
	 */
	public $watchFormFactory;
	
	/**
	 *
	 * @var \App\Forms\MembersFormFactory
	 * @inject
	 */
	public $membersFormFactory;
	
	private $troop;

	public function createComponentWatchForm() {
		if ($this->user->isLoggedIn()) {
			$this->watchFormFactory->setSeason($this->season);
			$watchId = $this->getParameter('id');
			$this->watchFormFactory->setId($watchId);
			$raceId = $this->getParameter('race');
			$this->watchFormFactory->setRace($raceId);
			$this->watchFormFactory->setTroop($this->troop);
			$form = $this->watchFormFactory->create();
			$form->onSuccess[] = function ($form) {
				$this->flashMessage("Hlídka byla založena.");		
				$link = $this->link("Watch:members");
				$form->getPresenter()->redirectUrl($link);
			};
			return $form;
		} else {
			throw new \Skautis\Wsdl\AuthenticationException("Pro založení hlídky je třeba se přihlásit");
		}
	}
	
	public function createComponentMembersForm() {
		if ($this->user->isLoggedIn()) {			
			$form = $this->membersFormFactory->create();
			$form->onSuccess[] = function ($form) {
				$this->redrawControl("members");
				if ($this["membersForm"]["send"]->isSubmittedBy()) {
					$this->redirect("Watch:review");
				}
				if ($this["membersForm"]["preview"]->isSubmittedBy()) {
					$this->redirect("Watch:create");
				}
			};
			return $form;
		} else {
			throw new \Skautis\Wsdl\AuthenticationException("Pro založení hlídky je třeba se přihlásit");
		}
	}
	
	public function setStep($step) {
		$this->step = $step;
	}

	public function renderCreate($raceId, $watchId) {
		if ($this->user->isLoggedIn()) {
			if ($this->getSession()->hasSection('watch')) {
				$watch = $this->watchRepository->createWatchFromSession($this->getSession('watch'));				
				$this->troop = $watch->troop;				
				$this["watchForm"]->setDefaults($this->watchRepository->getDataForForm($watch));
			}			
			$this->template->form = $this->template->_form = $this['watchForm'];
		} else {
			throw new \Skautis\Wsdl\AuthenticationException("Pro založení hlídky je třeba se přihlásit");
		}
	}
	
	public function handleTroop() {
		if($this->isAjax()) {
			$unitID = $this->getHttpRequest()->getPost('unitID');
			$this->troop = $this->unitRepository->getUnit($unitID);
			$this->redrawControl("group");			
		}
	}
	
	public function handleLoadPersons() {
		if($this->isAjax()) {			
			$unitID = $this->getHttpRequest()->getPost('unitID');		
			$members = $this->personRepository->getPersonsByUnit($unitID);						
			$this->template->persons = $members;
			$this->template->roles = $this->watchRepository->getRoles();
			$this->redrawControl("persons");			
		}
	}
	
	public function handleDeleteMember($memberId) {
		if($this->isAjax()) {
			$section = $this->getSession("watch");
			unset($section->members[$memberId]);
			unset($section->roles[$memberId]);
			$this->redrawControl("members");
		}
	}
	
	public function handleDeleteAllMembers() {
		if($this->isAjax()) {
			$section = $this->getSession("watch");
			unset($section->members);
			unset($section->roles);
			$this->redrawControl("members");			
		}
	}
	
	public function handleSave($raceId) {
		$watch = $this->watchRepository->createWatchFromSession($this->getSession('watch'));		
		$save = $this->watchRepository->save($watch);
		$this->getSession('watch')->remove();
		if ($save) {			
			$this->redirect("Race:detail $raceId");
		}
	}

	public function renderMembers($watchId, $raceId) {		
		$this->template->members = ($this->getSession('watch')->members) 
				? $this->getSession('watch')->members
				: array();
		$roles = array();		
		foreach ($this->template->members as $key => $value) {
			$rolesSession = $this->getSession('watch')->roles;
			$roles[$key] = $this->personRepository->getRoleName($rolesSession[$key]);
		}
		$this->template->rolesPicked = $roles;
	}
	
	public function renderReview() {
		$this->template->watch = $this->watchRepository->createWatchFromSession($this->getSession('watch'));
		if ($this->template->watch->category == \Watch::CATEGORY_NONCOMPETIVE) {
			$this->template->comment = $this->template->watch->nonCompetitiveReason;
		}
	}

	public function renderDefault() {/*
		$this->template->statewide = $this->raceRepository->getStatewideRound($this->season);
		$this->template->regions = $this->raceRepository->getRegions();
		$this->template->races = $this->raceRepository->getRaces($this->season);*/
	}
	
	public function renderDetail($id) {
		try {
			$watch = $this->watchRepository->getWatch($id);
			if ($this->user->isInRole('admin') || 
			($this->user->isInRole('raceManager') && $watch->isInRace($this->user->race)) ||
			($watch->author->id == $this->user->id) ) {
				$this->template->watch = $watch;
				$this->template->comment = $this->template->watch->nonCompetitiveReason;
				$this->template->races = $this->template->watch->getRaces();
			} else {
				throw new \Nette\Security\AuthenticationException("Nemáte oprávnění pracovat s hlídkou $id");
			}
		} catch (\Nette\InvalidArgumentException $ex) {
			$this->error($ex);
		}
	}
	/*
	public function renderEdit($id) {
		try {
			$race = $this->raceRepository->getRace($id);
			if(!$this->user->isInRole('admin') && !$race->canEdit($this->user->id)) {
				throw new Nette\Security\AuthenticationException("Nemáte požadovaná oprávnění!");				
			}
			$this->template->editors = $this->raceRepository->getEditors($id);
			$this->template->race = $race;
			//\Tracy\Dumper::dump();exit;
			$this["raceForm"]->setDefaults($this->raceRepository->getDataForForm($id));
		} catch (\Nette\InvalidArgumentException $ex) {
			$this->error($ex);
		}
	}*/
}
