<?php

namespace App\Presenters;

use Nette,
	Nette\Mail\Message,
	Nette\Mail\SendmailMailer;

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
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}			
		$watchId = $this->getParameter('id');
		$this->watchFormFactory->setId($watchId);
		$raceId = $this->getParameter('race');
		$this->watchFormFactory->setRace($raceId);
		$this->watchFormFactory->setTroop($this->troop);
		$form = $this->watchFormFactory->create();
		$form->onSuccess[] = function ($form) {				
			$watchId = $this->getParameter('id');
			if ($this["watchForm"]["send"]->isSubmittedBy()) {
				$this->redirect("Watch:members");
			}
			if ($this["watchForm"]["save"]->isSubmittedBy()) {					
				$this->redirect("Watch:detail", $watchId);
			}
		};
		return $form;		
	}
	
	public function createComponentMembersForm() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$watchId = $this->getParameter('watchId');
		$this->membersFormFactory->setId($watchId);
		$raceId = $this->getParameter('raceId');
		$this->membersFormFactory->setRace($raceId);
		$this->membersFormFactory->setTroop($this->troop);
		$form = $this->membersFormFactory->create();
		$form->onSuccess[] = function () {
			$watchId = $this->getParameter('watchId');
			$this->redrawControl("members");
			$this->redrawControl("flashes");
			if ($this["membersForm"]["send"]->isSubmittedBy()) {
				$this->redirect("Watch:review");
			}
			if ($this["membersForm"]["save"]->isSubmittedBy()) {					
				$this->redirect("Watch:detail", $watchId);
			}
			if ($this["membersForm"]["preview"]->isSubmittedBy()) {
				$this->redirect("Watch:create");
			}
		};
		return $form;		
	}
	
	public function setStep($step) {
		$this->step = $step;
	}

	public function renderCreate($race) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$this->getSession("watch")->remove();			
		if ($this->getSession()->hasSection('watch')) {				
			$watch = $this->watchRepository->createWatchFromSession($this->getSession('watch'));				
			$this->troop = $watch->troop;					
			$this["watchForm"]->setDefaults($this->watchRepository->getDataForForm($watch));
		}			
		$this->template->form = $this->template->_form = $this['watchForm'];		
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
			$this->template->roles = $this->personRepository->getRoles();
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
		$race = $this->raceRepository->getRace($raceId);
		$token = md5(uniqid(mt_rand(), true));		
		$watchId = $this->watchRepository->save($watch);
		$savedWatch = $this->watchRepository->getWatch($watchId);
		$savedWatch->setToken($raceId, $token);
		$this->getSession('watch')->remove();
		if ($savedWatch) {	
			$this->sendConfirmMail($race, $savedWatch);
			$this->redirect("Race:detail", $raceId);
		}
	}
	
	public function renderConfirm() {
		$watchId = $this->getParameter('watchId');
		$token = $this->getParameter('token');
		$watch = $this->watchRepository->getWatch($watchId);
		$success = $watch->confirm($token);
		$this->template->watch = $watch;
		if ($success == 1) {
			$this->template->success = TRUE;
		} else {
			$this->template->success = FALSE;
		}		
	}
	
	public function renderMy() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		//všechny, které založil akt. uživatel		
		$author = $this->watchRepository->getWatchsByAuthor($this->user->id);	
		//pro činovníky / administrátory střediska / oddílu
		if ($this->user->isOfficial()) {
			$administrator = $this->watchRepository->getWatchsByUnit($this->skautIS->getUser()->getUnitId());
		}
		//jsem účastníkem
		$participant = $this->watchRepository->getWatchsByParticipant($this->user->id);		
		$this->template->watchs = $author + $administrator + $participant;
		krsort($this->template->watchs);
		$roles = array();
		foreach (array_keys($this->template->watchs) as $key) {
			if (array_key_exists($key, $author)) {
				$roles[$key] = "Správce hlídky";
			} else if (array_key_exists($key, $administrator)) {
				$roles[$key] = "Hlídka mé jednotky";
			} else if (array_key_exists($key, $participant)) {
				$roles[$key] = "Člen hlídky";
			}
		}
		$this->template->roles = $roles;		
	}
	
	private function sendConfirmMail($race, $watch) {
		$latte = new \Latte\Engine;
		$params = array(
			"watch" => $watch,
			"raceTitle" => $race->title,
			"token" => $watch->getToken($race->id)
		);
		$mail = new Message();
		$mail->setFrom('Web skautských závodů <405245@mail.muni.cz>')
				->addTo($watch->emailLeader)
				->setHtmlBody($latte->renderToString(__DIR__ . '/templates/Mail/confirmWatch.latte', $params));
		$mailer = new SendmailMailer;
		$mailer->send($mail);
	}

	public function renderMembers($watchId, $raceId) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
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
	
	public function renderEditMembers($watchId, $raceId) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$watch = $this->watchRepository->getWatch($watchId);
		if (!$this->user->isInRole('admin') && 
				!($this->user->isInRole('raceManager') && $watch->isInRace($this->user->race))
				&& ($watch->author->id != $this->user->id)
				&& !($this->skautIS->getUser()->getUnitId() == $watch->group->id && $this->user->isOfficial()) 
				&& !($this->skautIS->getUser()->getUnitId() == $watch->troop->id && $this->user->isOfficial())) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}
		$section = $this->getSession("watch");
		unset($section->basic);
		$race = $this->raceRepository->getRace($raceId);								
		$this->template->watch = $watch;
		$this->template->race = $race;
		if ($section->members) {
			$this->template->members = $section->members;
			$roles = array();
			foreach ($this->template->members as $key => $value) {
				$rolesSession = $section->roles;
				$roles[$key] = $this->personRepository->getRoleName($rolesSession[$key]);
			}
		} else {
			$roles = array();
			$this->template->members = array();
			foreach ($watch->getMembers($race) as $member) {
				$this->template->members[$member->personId] = $member->displayName;
				$roles[$member->personId] = $member->getRoleName($raceId);
			}
		}				

		$this->template->rolesPicked = $roles;				
	}
	
	public function renderReview() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$this->template->watch = $this->watchRepository->createWatchFromSession($this->getSession('watch'));
		if ($this->template->watch->category == \Watch::CATEGORY_NONCOMPETIVE) {
			$this->template->comment = $this->template->watch->nonCompetitiveReason;
		}
	}
	
	public function renderEdit($id) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$watch = $this->watchRepository->getWatch($id);
		if (!$this->user->isInRole('admin') && 
				!($this->user->isInRole('raceManager') && $watch->isInRace($this->user->race))
				&& ($watch->author->id != $this->user->id)
				&& !($this->skautIS->getUser()->getUnitId() == $watch->group->id && $this->user->isOfficial()) 
				&& !($this->skautIS->getUser()->getUnitId() == $watch->troop->id && $this->user->isOfficial())) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}			
		$section = $this->getSession("watch");
		$section->remove();				
		$data = $this->watchRepository->getDataForForm($watch);				
		$this->troop = $watch->troop;				
		$this["watchForm"]->setDefaults($data);				
		$this->template->watch = $watch;
		$this->template->form = $this->template->_form = $this['watchForm'];			
	}
	
	public function renderDetail($id) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$watch = $this->watchRepository->getWatch($id);
		if (!$this->user->isInRole('admin') && 
				!($this->user->isInRole('raceManager') && $watch->isInRace($this->user->race))
				&& ($watch->author->id != $this->user->id)
				&& !($this->skautIS->getUser()->getUnitId() == $watch->group->id && $this->user->isOfficial()) 
				&& !($this->skautIS->getUser()->getUnitId() == $watch->troop->id && $this->user->isOfficial())) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}
		$this->template->watch = $watch;
		$this->template->comment = $this->template->watch->nonCompetitiveReason;
		$this->template->races = $this->template->watch->getRaces();
			
	}	
}
