<?php

namespace App\Presenters;

use Nette,
	App\Model,
	Tracy\ILogger;


/**
 * Error presenter.
 */
class ErrorPresenter extends BasePresenter
{
	/** @var ILogger */
	private $logger;


	public function __construct(ILogger $logger)
	{		
		$this->logger = $logger;
	}


	/**
	 * @param  Exception
	 * @return void
	 */
	public function renderDefault($exception)
	{
		if ($this->isAjax()) { // AJAX request? Note this error in payload.
			$this->payload->error = TRUE;
			$this->payload->message = $exception->getMessage();
			$this->terminate();
		}
		// Pokud nenalezeno ID záznamu a záznam nebyl nalezen, zobrazíme informaci uživateli.
		if ($exception instanceof \Race\DbNotStoredException) {					
			$this->flashMessage($exception->getMessage(), 'error');
			$this->redirect("Homepage:");
			exit;
		}
		// Pokud je chyba vyvolaná odhlášením, přesměrujeme na přihlašovací stránku
		if ($exception instanceof \Skautis\Wsdl\AuthenticationException || 
			$exception instanceof \Nette\Security\AuthenticationException) {
			$uri = $this->getHttpRequest()->getUrl();			
			$this->redirectUrl($this->skautIS->getLoginUrl($uri));
			exit;
		}
		// Pokud je chyba vyvolaná nedostatkem oprávnění pro přihlášeného uživatele,
		// přesměrujeme na hlavnímu stránku a uživatele informujeme
		if ($exception instanceof \Race\PermissionException) {
			$this->logger->log($exception, \Tracy\Debugger::ERROR);			
			$this->flashMessage($exception->getMessage());
			$this->redirect('Homepage:');
			exit;
		}
		// Ostatní hlídky zalogujeme a pošleme upozornění mailem
		if ($exception instanceof Nette\Application\BadRequestException) {
			$code = $exception->getCode();			
			// load template 403.latte or 404.latte or ... 4xx.latte
			$this->setView(in_array($code, array(403, 404, 500)) ? $code : '4xx');
			// log to access.log
			$this->logger->log("HTTP code $code: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", 'access');
			

		} else {
			$this->setView('500'); // Výchozí šablona 500.latte
			$this->logger->log($exception, ILogger::EXCEPTION);
		}
	}

}
