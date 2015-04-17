<?php

namespace App\Presenters;

use Nette;

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

	public function createComponentRaceForm() {
		if ($this->user->isLoggedIn()) {
		$this->raceFormFactory->setSeason($this->season);
		$raceId = $this->getParameter('id');
		$this->raceFormFactory->setId($raceId);
		$form = $this->raceFormFactory->create();
		$form->onSuccess[] = function ($form) {
			$this->flashMessage("Závod byl založen.");		
			$link = $this->link("Race:");
			$form->getPresenter()->redirectUrl($link);
		};
		return $form;
		} else {
			throw new \Skautis\Wsdl\AuthenticationException("Pro založení závodu je třeba se přihlásit");
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
