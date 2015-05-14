<?php


namespace App\Presenters;

use Nette;

/**
 * Description of MailPresenter
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class MailPresenter extends BasePresenter {
	
	/**
	 *
	 * @var \App\Forms\MailFormFactory
	 * @inject
	 */
	public $mailFormFactory;
	
	/**
	 *
	 * @var \RaceRepository
	 * @inject
	 */
	public $raceRepository;
	
	
	public function createComponentEmailForm() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$form = $this->mailFormFactory->create();
		$form->onSuccess[] = function () {
			$id = $this->getParameter('id');
			$this->redirect("Race:administrate", $id);
		};
		return $form;		
	}
	
	public function renderRace($id) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		if (!($this->user->isInRole('admin') 
				&& !($this->user->isInRole('raceManager') && in_array($id, $this->user->identity->data["races"])))) {
			throw new \Race\PermissionException("Nemáte požadovaná oprávnění!");
		}
		$race = $this->raceRepository->getRace($id);
		$this->template->race = $race;
		$this->mailFormFactory->addRecieverGroups(array(1, 2, 4, 5, 6, 7, 8));
		$this->mailFormFactory->setRace($id);
		$this->mailFormFactory->canAddEmails();
	}
}
