<?php

namespace App\Presenters;

use Nette,
	Nette\Application\UI\Multiplier;

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
	 * @var \App\Forms\PointsFormFactory
	 * @inject
	 */
	public $pointsFormFactory;
	
	private $watchs;

	public function createComponentRaceForm() {
		if ($this->user->isLoggedIn()) {
		$this->raceFormFactory->setSeason($this->season);
		$raceId = $this->getParameter('id');
		$this->raceFormFactory->setId($raceId);
		$form = $this->raceFormFactory->create();
		$form->onSuccess[] = function ($form) {
			$this->flashMessage("Závod byl založen.");
			$this->redirect("Race:");
		};
		return $form;
		} else {
			throw new \Skautis\Wsdl\AuthenticationException("Pro založení závodu je třeba se přihlásit");
		}
	}
	
	public function createComponentPointsForm() {
		if($this->skautIS->getUser()->isLoggedIn()) {						
			$this->pointsFormFactory->setWatchs($this->watchs);
			$id = $this->getParameter('id');
			$this->pointsFormFactory->setRace($id);	
			$form = $this->pointsFormFactory->create();
			$form->onSuccess[] = function () {
			$id = $this->getParameter('id');
			$this->flashMessage("Vysledky byly zadány.");		
			$this->redirect("Race:detail", $id);
		};	
		return $form;
		} else {
			throw new \Skautis\Wsdl\AuthenticationException("Pro tuto funkci je nutné se přihlásit");
		}
	}		
	
	public function renderCreate() {
		if ($this->user->isLoggedIn()) {
			
		} else {
			throw new \Skautis\Wsdl\AuthenticationException("Pro založení závodu je třeba se přihlásit");
		}
	}
	
	public function renderDefault() {
		$this->template->statewide = $this->raceRepository->getStatewideRound($this->season);
		$this->template->regions = $this->raceRepository->getRegions();
		$this->template->races = $this->raceRepository->getRaces($this->season);
	}
	
	public function renderDetail($id) {
		try {
			$this->template->race = $this->raceRepository->getRace($id);
			$this->template->watchs = $this->watchRepository->getWatchs($id);
		} catch (\Nette\InvalidArgumentException $ex) {
			$this->error($ex);
		}
	}
	
	public function renderAdministrate($id) {
		try {
			$this->template->race = $this->raceRepository->getRace($id);
			$this->watchs = $this->watchRepository->getWatchs($id);
			$this->template->watchs = $this->watchs;
			//var_dump("render");exit;
		} catch (\Nette\InvalidArgumentException $ex) {
			$this->error($ex);
		}
	}

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
	}
	
}
