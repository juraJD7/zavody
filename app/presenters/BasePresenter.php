<?php

namespace App\Presenters;

use Nette,
	App\Model;


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
	
	public function startup() {
		parent::startup();			
				
		if($this->skautIS->getUser()->isLoggedIn()) {			
			$this->template->url = $this->link('Homepage:logout');
			$this->template->text = "Odhlásit se";
			
		} else {
			$this->template->url = $this->skautIS->getLoginUrl($this->link('//this'));
			$this->template->text = "Přihlásit se";
			$this->template->role="guest";
			$this->template->login="guest";		
		}
	}
}
