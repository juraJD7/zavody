<?php

namespace App\Forms;

use Nette,
	Nette\Application\UI\Form,
	Nette\Mail\Message;

/**
 * Description of MailFormFactory
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class MailFormFactory extends BaseFormFactory {
	
	private $recieverGroups = array();
	private $canAdd = false;
	private $mailRepository;
	private $raceRepository;
	private $race;
	private $from;
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param Nette\Database\Context $database
	 * @param \RaceRepository $raceRepository
	 * @param \MailRepository $mailRepository
	 */
	public function __construct(\Skautis\Skautis $skautIS, Nette\Database\Context $database, \RaceRepository $raceRepository, \MailRepository $mailRepository) {
		parent::__construct($skautIS, $database);
		$this->mailRepository = $mailRepository;
		$this->raceRepository = $raceRepository;
	}
	
	public function addRecieverGroups($groupNumbers) {
		foreach ($groupNumbers as $number) {
			$this->recieverGroups[] = $number;
		}
	}
	
	public function canAddEmails() {
		$this->canAdd = TRUE;
	}
	
	public function setRace($race) {
		$this->race = $race;
	}
	
	public function setFrom($email) {
		$this->from = $email;
	}

	/**
	 * @return Form
	 */
	public function create() {
		
		$form = new Form;
		$groups = array();
		foreach ($this->recieverGroups as $groupNumber) {
			$groups[$groupNumber] = $this->mailRepository->getGroupName($groupNumber);			
		}
		$form->addCheckboxList('groups', "Skupiny příjemců", $groups);
		if ($this->canAdd) {
			$form->addTextArea('otherEmails', "Další příjemci: ");
		}
		$form->addText('subject', 'Předmět:');
		$form->addTextArea('body', "Text emailu:", 50, 20);
		
		$form->onSuccess[] = array($this, 'formSucceeded');
		$form->addSubmit('send', "Odeslat");
		
		$renderer = $form->getRenderer();
		$this->addBootstrapRendering($renderer, $form);
		
		return $form;
	}
	
	public function formSucceeded(Form $form) {
		$values = $form->getValues();
		$counter = 0;
		$mail = new Message();
		$mail->setSubject($values->subject)
			->setBody($values->body);
		
		if ($this->from) {
			$mail->setFrom($this->from);
		} else {
			$mail->setFrom('Web skautských závodů <405245@mail.muni.cz>');
		}
	
		if (in_array(\Mail::ADMINISTRATORS, $values->groups)) {
			$counter += $this->addAdministrators($mail);
		}
		
		$mailer = new Nette\Mail\SendmailMailer();
		//$mailer->send($mail);
		$form->getPresenter()->flashMessage("Email byl poslán " . $counter . " kontaktům.");
	}
	
	public function addAdministrators(Message &$mail) {
		$emails = $this->mailRepository->getAdministratorEmails();		
		foreach ($emails as $email) {
			$mail->addTo($email);
		}
		return count($emails);
	}
}
