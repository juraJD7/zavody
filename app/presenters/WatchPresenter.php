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
	private $raceId;

	public function createComponentWatchForm() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		//nastavení parametrů továrny
		$watchId = $this->getParameter('id');
		$this->watchFormFactory->setId($watchId);
		$raceId = $this->getParameter('race');
		$this->watchFormFactory->setRace($raceId);
		$this->watchFormFactory->setTroop($this->troop);
		$form = $this->watchFormFactory->create();
		$form->onSuccess[] = function () {				
			$watchId = $this->getParameter('id');
			//při vytváření hlídky -> přechod na další krok
			if ($this["watchForm"]["send"]->isSubmittedBy()) {
				$this->redirect("Watch:members");
			}
			// editace hlídky -> po uložení rovnou zobrazí detail hlídky
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
		//nastavení parametrů pro továrnu
		$watchId = $this->getParameter('watchId');
		$this->membersFormFactory->setId($watchId);
		$this->raceId = $this->getParameter('raceId');
		$this->membersFormFactory->setRace($this->raceId);
		$this->membersFormFactory->setTroop($this->troop);
		$form = $this->membersFormFactory->create();
		$form->onSuccess[] = function () {
			$watchId = $this->getParameter('watchId');
			$this->redrawControl("members");
			//invalidace flash zpráv pro zobrazení nových zpráv ajaxem
			$this->redrawControl("flashes");
			// při zakládání hlídky - přechod na další krok
			if ($this["membersForm"]["send"]->isSubmittedBy()) {
				$this->redirect("Watch:review");
			}
			// při přímé editaci členů - po uložení přesměrování na detail hlídky
			if ($this["membersForm"]["save"]->isSubmittedBy()) {					
				$this->redirect("Watch:detail", $watchId);
			}
			// při vytváření hlídky - přechod na předchozí krok
			if ($this["membersForm"]["preview"]->isSubmittedBy()) {
				$this->redirect("Watch:create");
			}
		};
		return $form;		
	}
	
	public function renderCreate($race) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}			
		if ($this["watchForm"]->hasErrors()) {
			$this->troop = $this["watchForm"]["troop"]->getValue();
		}
		//pokud existuje záznam v session (např. při kroku zpět při přihlašování), načte výchozí hodnoty
		if ($this->getSession()->hasSection('watch')) {				
			$watch = $this->watchRepository->createWatchFromSession($this->getSession('watch'));	
						$data = $this->watchRepository->getDataForForm($watch);
			if ($this->troop) {
				unset($data["group"]);
			}
			if (!$this->troop) {
				$this->troop = $watch->troop;				
			}
			$this["watchForm"]->setDefaults($data);
			
		}		
		$this->template->form = $this->template->_form = $this['watchForm'];		
	}
	
	/**
	 * Načtení oddílů do formuláře při změně střediska v kroku 1 přihlašování
	 */
	public function handleTroop() {
		if($this->isAjax()) {
			$unitID = $this->getHttpRequest()->getPost('unitID');
			$this->troop = $this->unitRepository->getUnit($unitID);
			$this->redrawControl("group");			
		}
	}
	
	/**
	 * Načtení členů zvolené jednotky v kroku 2 přihlašování
	 */
	public function handleLoadPersons() {
		if($this->isAjax()) {			
			$unitID = $this->getHttpRequest()->getPost('unitID');		
			$members = $this->personRepository->getPersonsByUnit($unitID);						
			$this->template->persons = $members;
			//seznam dostupných rolí pro výběr
			$this->template->roles = $this->personRepository->getRoles();
			$this->redrawControl("persons");			
		}
	}
	
	/**
	 * Smaže člena z vybraných členů v kroku 2 přihlašování 
	 * 
	 * @param int $memberId
	 */
	public function handleDeleteMember($memberId) {
		if($this->isAjax()) {
			$section = $this->getSession("watch");
			unset($section->members[$memberId]);
			unset($section->roles[$memberId]);
			$this->redrawControl("members");
		}
	}
	
	/**
	 * Smaže všechnyvybané členy v kroku 2 přihlašování 
	 */
	public function handleDeleteAllMembers() {
		if($this->isAjax()) {
			$section = $this->getSession("watch");
			$section->members = array();
			$section->roles = array();
			$this->redrawControl("members");			
		}
	}
	
	/**
	 * Vytvoří hlídku ze session a uloží do databáze
	 * 
	 * @param type $raceId
	 */
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
	
	/**
	 * Stránka pro potvrzení účasti hlídky vedoucím oddílu
	 * 
	 * Nevyžaduje přihlášení do skautISu
	 */
	public function renderConfirm() {
		$watchId = $this->getParameter('watchId');
		$token = $this->getParameter('token');
		$watch = $this->watchRepository->getWatch($watchId);
		$success = $watch->confirm($token);
		$this->template->watch = $watch;
		//informace o úspěšném či neúspěšném potvrzení se projeví v šabloně
		if ($success == 1) {
			$this->template->success = TRUE;
		} else {
			$this->template->success = FALSE;
		}		
	}
	
	/**
	 * Stránka zobrazí všechny hlídky přihlášeného uživatele
	 * 
	 * @throws Nette\Security\AuthenticationException
	 */
	public function renderMy() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		//všechny hlídky, které založil přihlášený uživatel		
		$author = $this->watchRepository->getWatchsByAuthor($this->user->id);	
		//hlídky podle jednotky pro činovníky oddílu či střediska
		$administrator = array();
		if ($this->user->isOfficial()) {
			$administrator = $this->watchRepository->getWatchsByUnit($this->skautIS->getUser()->getUnitId());
		}
		//hlídky, kde je uživatel účastníkem
		$participant = $this->watchRepository->getWatchsByParticipant($this->user->id);		
		$this->template->watchs = $author + $administrator + $participant;
		// seřazení hlídek a přidělení rolí uživatele v jednotlivých hlídkách,
		// správce hlídky má přednost před rolí činovníka dané jednotky a ta má přednost
		// před rolí běžného účastníka hlídky
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
	
	/**
	 * Zašle potvrzovací email s údaji o hlídce vedoucímu oddílu
	 * 
	 * @param Race $race
	 * @param Watch $watch
	 */
	private function sendConfirmMail($race, $watch) {
		//vytvoření šablony emailu s parametry
		$latte = new \Latte\Engine;
		$params = array(
			"watch" => $watch,
			"raceTitle" => $race->title,
			"token" => $watch->getToken($race->id)
		);
		//vytovření zprávy a odeslání
		$mail = new Message();
		$mail->setFrom('Web skautských závodů <405245@mail.muni.cz>')
				->addTo($watch->emailLeader)
				->setHtmlBody($latte->renderToString(__DIR__ . '/templates/Mail/confirmWatch.latte', $params));
		$mailer = new SendmailMailer;
		$mailer->send($mail);
	}
	
	/**
	 * Přihlašovací proces - krok 2, přidávání členů
	 * 
	 * @param int $watchId ID hlídky
	 * @param int $raceId ID závodu, do kterého se hlídka přihlašuje 
	 * @throws Nette\Security\AuthenticationException
	 */
	public function renderMembers($watchId, $raceId) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		//vytvoření seznamu již přidaných členů ze session
		$this->template->members = ($this->getSession('watch')->members) 
				? $this->getSession('watch')->members
				: array();
		//přidělení rolí jednotlivým vybraným členům ze session
		$roles = array();		
		foreach ($this->template->members as $key => $value) {
			$rolesSession = $this->getSession('watch')->roles;
			$roles[$key] = $this->personRepository->getRoleName($rolesSession[$key]);
		}
		$this->template->rolesPicked = $roles;
	}
	
	/**
	 * Úprava členů hlídky v zadaném závodě
	 * 
	 * @param int $watchId ID hlídky
	 * @param int $raceId ID závodu, kterého se změny členů týkají
	 * @throws Nette\Security\AuthenticationException
	 * @throws \Race\PermissionException
	 */
	public function renderEditMembers($watchId, $raceId) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$watch = $this->watchRepository->getWatch($watchId);
		//upravovat může činovník oddílu či střediska hlídky nebo autor hlídky
		if (($watch->author->id != $this->user->id)
				&& !($this->skautIS->getUser()->getUnitId() == $watch->group->id && $this->user->isOfficial()) 
				&& !($this->skautIS->getUser()->getUnitId() == $watch->troop->id && $this->user->isOfficial())) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}
		$section = $this->getSession("watch");
		unset($section->basic);
		$race = $this->raceRepository->getRace($raceId);								
		$this->template->watch = $watch;
		$this->template->race = $race;
		//pokud uživatelé nejsou v session, načteme je z hlídky, která je editována
		if (!isset($section->members)) {
			$roles = array();
			$section->members = array();
			$section->roles = array();
			foreach ($watch->getMembers($race) as $member) {
				$section->members[$member->personId] = $member->displayName;
				$section->roles[$member->personId] = $member->getRoleId($raceId);
				$section->units[$member->personId] = $member->unit->id;
			}
		}	
		//uložení členů a jejich rolí do šablony pro jejich zobrazení
		$this->template->members = $section->members;
		$roles = array();
		foreach ($this->template->members as $key => $value) {
			$rolesSession = $section->roles;
			$roles[$key] = $this->personRepository->getRoleName($rolesSession[$key]);
		}	
		$this->template->rolesPicked = $roles;				
	}
	
	/**
	 * Zobrazí přehled údajů z přihlašovaní hlídky, vč. soutěžní kateogire a případného odůvodnění u kategorie nesoutěžní
	 * 
	 * @throws Nette\Security\AuthenticationException
	 */
	public function renderReview() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$this->template->watch = $this->watchRepository->createWatchFromSession($this->getSession('watch'));
		if ($this->template->watch->category == \Watch::CATEGORY_NONCOMPETIVE) {
			$this->template->comment = $this->template->watch->nonCompetitiveReason;
		}
	}
	
	/**
	 * Zobrazí možnost úpravy společných parametrů hlídky pro všehcny její závody
	 * 
	 * @param int $id ID hlídky
	 * @throws Nette\Security\AuthenticationException
	 * @throws \Race\PermissionException
	 */
	public function renderEdit($id) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$watch = $this->watchRepository->getWatch($id);
		//upravit hlídku může pouze administrátor, editor závodu, kterého se hlídka účastní,
		//autor hlídky a činovník jejího oddílu nebo střediska
		if (!$this->user->isInRole('admin') && 
				!($this->user->isInRole('raceManager') && $watch->isInRaces($this->user->identity->data["races"]))
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
	
	/**
	 * Zobrazí detail hlídky
	 * 
	 * @param int $id
	 * @throws Nette\Security\AuthenticationException
	 * @throws \Race\PermissionException
	 */
	public function renderDetail($id) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$watch = $this->watchRepository->getWatch($id);
		//zobrazit detail hlídky může pouze administrátor, editor závodu, kterého se hlídka účastní,
		//autor hlídky a činovník jejího oddílu nebo střediska
		if (!$this->user->isInRole('admin') && 
				!($this->user->isInRole('raceManager') && $watch->isInRaces($this->user->identity->data["races"]))
				&& ($watch->author->id != $this->user->id)
				&& !($this->skautIS->getUser()->getUnitId() == $watch->group->id && $this->user->isOfficial()) 
				&& !($this->skautIS->getUser()->getUnitId() == $watch->troop->id && $this->user->isOfficial())) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}
		$this->template->watch = $watch;
		$this->template->comment = $this->template->watch->nonCompetitiveReason;
		$this->template->races = $this->template->watch->getRaces();
			
	}	
	
	/**
	 * Smaže hlídku ze závodu
	 * 
	 * @param int $watchId
	 * @param int $raceId
	 */
	public function handleDeleteWatch($watchId, $raceId) {
		if ($this->isAjax()) {
			$race = $this->raceRepository->getRace($raceId);
			if ($race->applicationDeadline < date('Y-m-d')) {
				$res = $this->watchRepository->deleteWatch($watchId, $raceId);
				$advancedRaces = array('K', 'C');
				//pokud byla smazána hlídka z navazujícího závodu, zruší postup v předcházejícím závodě
				//a umožní postup jiné hlídce
				if ($res && in_array($race->round->short, $advancedRaces)) {
					$prevRace = $this->raceRepository->getPrevRace($watchId, $race);
					$watch = $this->watchRepository->getWatch($watchId);
					$this->watchRepository->unsetAdvance($watch, $prevRace);
				}
			}			
			$this->redrawControl();
		}	
		
	}
	
	/**
	 * Zruší rozpracované přihlašování hlídky a vymaže session
	 */
	public function handleCancelWatch() {
		$this->session->getSection("watch")->remove();
		$this->redirect("Race:");
	}
}
