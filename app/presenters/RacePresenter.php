<?php

namespace App\Presenters;

use Nette,
	Nette\Application\UI\Multiplier,
	Nette\Mail\Message,
	Nette\Mail\SendmailMailer;

/**
 * Description of RacePresenter
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class RacePresenter extends BasePresenter {
	
	/** 
	 * @var \App\Forms\RaceFormFactory
	 * @inject 
	 */
	public $raceFormFactory;
	
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
	 * @var \QuestionRepository
	 * @inject
	 */
	public $questionRepository;
	
	/**
	 *
	 * @var \ArticleRepository
	 * @inject
	 */
	public $articleRepository;
	
	/**
	 *
	 * @var \PhotoRepository
	 * @inject
	 */
	public $photoRepository;
	
	/**
	 *
	 * @var \App\Forms\PointsFormFactory
	 * @inject
	 */
	public $pointsFormFactory;
	
	private $watchs;
	private $raceId;
	private $editors = array();

	public function createComponentRaceForm() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}	
		$raceId = $this->getParameter('id');
		$this->raceFormFactory->setId($raceId);
		$this->raceFormFactory->setEditors($this->editors);
		$form = $this->raceFormFactory->create();
		$form->onSuccess[] = function () {
			$this->loginRefresh();
			$this->flashMessage("Závod byl založen.");
			$this->redirect("Race:");
		};
		return $form;		
	}
	
	public function createComponentPointsForm() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}	
		$id = $this->getParameter('id');
		//přístup mají pouze administrátoři a editoří závodu
		if (!$this->user->isInRole('admin')
				&& !($this->user->isInRole('raceManager') && in_array($id, $this->user->identity->data["races"]))) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}						
		$this->pointsFormFactory->setWatchs($this->watchs);		
		$this->pointsFormFactory->setRace($id);	
		$form = $this->pointsFormFactory->create();
		$form->onSuccess[] = function () {
			$id = $this->getParameter('id');
			//po úspěšném zadání výsledků pošle email veliteli závodu
			$this->sendConfirmMail($id);
			//přesměrování na detail závodu
			$id = $this->getParameter('id');
			$this->flashMessage("Vysledky byly zadány.");		
			$this->redirect("Race:detail", $id);
		};	
		return $form;		
	}	
	
	/**
	 * Pošle email vedoucímu závodu potvrzovací email s výsledky závodu
	 * 
	 * @param type $raceId
	 */
	private function sendConfirmMail($raceId) {
		$race = $this->raceRepository->getRace($raceId);
		//vygenerování 32-znakového tokenu
		$token = md5(uniqid(mt_rand(), true));
		$race->setToken($token);
		//vytvoření šablony pro mail vč. parametrů
		$latte = new \Latte\Engine;
		$params = array(
			"race" => $race,
			"watchs" => $this->watchRepository->getWatchs($raceId)
		);
		//odeslání emailu
		$mail = new Message();
		$mail->setFrom('Web skautských závodů <405245@mail.muni.cz>')
				->addTo($race->commanderEmail)
				->setHtmlBody($latte->renderToString(__DIR__ . '/templates/Mail/confirmRace.latte', $params));
		$mailer = new SendmailMailer;
		$mailer->send($mail);
	}
	
	/**
	 * Pošle email s výsledky veliteli všem editorům, pořádající jednotce, všem vedoucím oddílů hlídek,
	 * veliteli závodu a hl. rozhodřímu
	 * 
	 * @param type $raceId
	 */
	private function sendResultMail($raceId) {
		//vytvoření šablony s parametry
		$race = $this->raceRepository->getRace($raceId);		
		$latte = new \Latte\Engine;
		$params = array(
			"race" => $race,
			"watchs" => $this->watchRepository->getWatchs($raceId)
		);
		//vytvoření zprávy
		$mail = new Message();
		//zaslání veliteli závodu
		$mail->setFrom($race->commanderEmail, $race->commander)			
				->addTo($race->commanderEmail);
				//zaslání hl. rozhodčímu
				if (filter_var($race->refereeEmail, FILTER_VALIDATE_EMAIL)) {
					$mail->addTo($race->refereeEmail);
				}			
				//zaslání na hl. kontakt závodu
				if (filter_var($race->email, FILTER_VALIDATE_EMAIL)) {
					$mail->addTo($race->email);
				}
				//zaslání na adresu pořádající jednotky
				if (filter_var($race->organizer->email, FILTER_VALIDATE_EMAIL)) {
					$mail->addTo($race->organizer->email);
				}				
		$mail->setHtmlBody($latte->renderToString(__DIR__ . '/templates/Mail/resultRace.latte', $params));
		//zaslání všem vedoucím oddílů přihlášených hlídek + rádcům, pokud mají vyplněnou adresu
		foreach ($this->watchRepository->getWatchs($raceId) as $watch) {
			if (filter_var($watch->emailGuide, FILTER_VALIDATE_EMAIL)) {
				$mail->addTo($watch->emailLeader);
			}
			if (filter_var($watch->emailGuide, FILTER_VALIDATE_EMAIL)) {
				$mail->addTo($watch->emailLeader);
			}
		}		
		//odeslání
		$mailer = new SendmailMailer;
		$mailer->send($mail);
	}
	
	/**
	 * Potvrzovací stránka pro potvrzení výsledků velitelem
	 * 
	 * Stránka nevyžaduje přihlášení pro případ, že velitel závodu nepoužívá skautIS.
	 * 
	 * @throws \Race\PermissionException
	 */
	public function renderConfirm() {
		$raceId = $this->getParameter('raceId');
		$token = $this->getParameter('token');
		$race = $this->raceRepository->getRace($raceId);
		if ($race->getToken() == $token) {
			$this->template->race = $race;
			$this->template->token = $token;
			$this->template->watchs = $this->watchRepository->getWatchs($raceId);
		} else {
			throw new \Race\PermissionException("Neplatný token.");
		}	
	}
	
	/**
	 * Stránka zobrazí všechny závody, které se vzrahují k přihlášenému uživateli
	 * 
	 * @throws Nette\Security\AuthenticationException
	 */
	public function renderMy() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		//všechny závody, kde je uživatel editorem		
		$editor = $this->raceRepository->getRacesByEditor($this->user->id);	
		//pro činovníky / administrátory pořádající jendotky se zobrazí závody pořádané jednotkou
		$administrator = array();
		if ($this->user->isOfficial()) {
			$administrator = $this->raceRepository->getRacesByOrganizer($this->skautIS->getUser()->getUnitId());
		}
		//všechny závody, kde je uživatel účastníkem
		$participant = $this->raceRepository->getRacesByParticipant($this->user->id);		
		$this->template->races = $editor + $administrator + $participant;
		//seřazení a přidělení rolí v závodě, v případě více rolí má přednost editor před rolí činovníka a činovnická role
		//má přednost před rolí účastníka závodu
		krsort($this->template->races);
		$roles = array();
		foreach (array_keys($this->template->races) as $key) {
			if (array_key_exists($key, $editor)) {
				$roles[$key] = "Editor závodu";
			} else if (array_key_exists($key, $administrator)) {
				$roles[$key] = "Činovník pořádající jednotky";
			} else if (array_key_exists($key, $participant)) {
				$roles[$key] = "Účastník závodu";
			}
		}
		$this->template->roles = $roles;		
	}
	
	/**
	 * Zpracuje signál při potvrzení výsledků a odešle emaily všem zúčasnněným osobám s výsledky
	 * 
	 * @throws \Race\PermissionException
	 */
	public function handleConfirm() {
		if($this->isAjax()) {
			$raceId = $this->getHttpRequest()->getPost('raceId');
			$token = $this->getHttpRequest()->getPost('token');
			$race = $this->raceRepository->getRace($raceId);
			//ověření tokenu
			if ($race->getToken() == $token) {
				$success = $race->confirm($token);
				if ($success == 1) {
					$this->sendResultMail($raceId);
					$this->flashMessage("Výsledky byly potvrzeny.");
				} else {
					$this->flashMessage("Výsledky se nepodařilo potvrdit.", 'error');
				}
				$this->invalidateControl();
			} else {
				throw new \Race\PermissionException("Neplatný token.");
			}
		}		
	}
	
	/**
	 * Vygeneruje účastníky ze všech hlídek do souboru PDF podle šablony
	 * 
	 * @param type $raceId
	 */
	public function actionExportParticipants($raceId) {
		$template = $this->createTemplate();
		$template->setFile(__DIR__ . "/templates/Export/participants.latte");
		$template->race = $this->raceRepository->getRace($raceId);
		$template->watchs = $this->watchRepository->getWatchs($raceId);
		
		$pdf = new \Joseki\Application\Responses\PdfResponse($template);
		// vynutí stažení souboru
		$pdf->setSaveMode(\Joseki\Application\Responses\PdfResponse::DOWNLOAD);
		$this->sendResponse($pdf);
	}
	
	/**
	 * Zobrazí stránku s formulářem pro založení závodu
	 * 
	 * @throws Nette\Security\AuthenticationException
	 * @throws \Race\PermissionException
	 */
	public function renderCreate() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		//výčet druhů jednotek, se kterou může uživatel založit závod
		$types = array("ustredi", "kraj", "okres", "stredisko");
		if (!in_array($this->user->unit->unitType, $types)) {
			throw new \Race\PermissionException("S touto rolí nelze založit závod. Musíte mít alespoň oprávnění střediska.");
		}
	}
	
	/**
	 * Zobrazí seznam všech závodů v aktuálním ročníku
	 */
	public function renderDefault() {
		$this->template->statewide = $this->raceRepository->getStatewideRound($this->season);
		$this->template->regions = $this->raceRepository->getRegions();
		$this->template->numRaces = $this->raceRepository->getNumRaces();
		$this->template->races = $this->raceRepository->getRaces($this->season);
	}
	
	/**
	 * Zobrazí detail závodu
	 * 
	 * @param int $id ID závodu
	 */
	public function renderDetail($id) {		
		$this->template->race = $this->raceRepository->getRace($id);
		$this->raceId = $id;
		$this->template->numArticle = $this->articleRepository->countAllRace($id);
		$this->template->numPhoto = $this->photoRepository->countAllRace($id);
		$this->template->numQuestion = $this->questionRepository->countAllRace($id);
		// pokud je uživatel přihlášený, zobrazí se navíc seznam přihlášených hlídek
		if ($this->user->isLoggedIn()) {
			$this->template->watchs = $this->watchRepository->getWatchs($id);
			usort($this->template->watchs, function ($a, $b) {
					$category = strcmp($a->category, $b->category);	
					if ($category != 0) {
						return $category;
					}
					return $a->getOrder($this->raceId) > $b->getOrder($this->raceId);
			});
		}
	}
	
	/**
	 * Stránka pro správu závodu editory
	 * 
	 * 
	 * @param int $id ID závodu
	 * @throws Nette\Security\AuthenticationException
	 * @throws \Race\PermissionException
	 */
	public function renderAdministrate($id) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		if (!$this->user->isInRole('admin')
				&& !($this->user->isInRole('raceManager') && in_array($id, $this->user->identity->data["races"]))) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}
		$this->template->race = $this->raceRepository->getRace($id);
		$this->watchs = $this->watchRepository->getWatchs($id);
		$this->template->watchs = $this->watchs;
		$this->template->unansweredQuestions = $this->questionRepository->getNumUnansweredQuestion($id);
	}
	
	/**
	 * Stránka pro úpravu parametrů závodu
	 * 
	 * Dostupná pouze pro editory závodu a adminstrátory webu
	 * 
	 * @param int $id ID závodu
	 * @throws Nette\Security\AuthenticationException
	 * @throws \Race\PermissionException
	 */
	public function renderEdit($id) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		if (!$this->user->isInRole('admin')
				&& !($this->user->isInRole('raceManager') && in_array($id, $this->user->identity->data["races"]))) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}
		$race = $this->raceRepository->getRace($id);			
		//vyplnění formuláře uloženými hodnotami
		$this->editors = $this->raceRepository->getEditors($id);
		$editorsArray = array();
		foreach ($this->editors as $editor) {
			$editorsArray[] = $editor->id;
		}
		$this->template->race = $race;			
		$this["raceForm"]->setDefaults($this->raceRepository->getDataForForm($id));
		$this["raceForm"]["editors_input"]->setDefaultValue($editorsArray);
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
				if ($res && in_array($race->round->short, $advancedRaces)) {
					$prevRace = $this->raceRepository->getPrevRace($watchId, $race);
					$watch = $this->watchRepository->getWatch($watchId);
					$this->watchRepository->unsetAdvance($watch, $prevRace);
				}
			}			
			$this->redrawControl();
		}	
		
	}
	
}
