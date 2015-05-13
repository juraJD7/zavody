<?php

namespace App\Presenters;

use Nette;

/**
 * Description of PhotoPresenter
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PhotoPresenter extends BasePresenter {
	
	/**
	 *
	 * @var \PhotoRepository
	 * @inject
	 */
	public $photoRepository;
	
	/** 
	 * @var \App\Forms\PhotoUploadFormFactory
	 * @inject 
	 */
	public $photoUploadFormFactory;
	
	/** 
	 * @var \App\Forms\PhotoDescriptionFormFactory
	 * @inject 
	 */
	public $photoDescriptionFormFactory;
	
	/**
	 *
	 * @var \RaceRepository
	 * @inject
	 */
	public $raceRepository;
	
	/**
	 *
	 * @var \Nette\Utils\Paginator
	 * @inject
	 */
	public $paginator;
	
	private $raceId;
	private $actionPaginator;
	protected $params = array();
	private $edit;
	private $page;
	private $photos;
	
	protected function createComponentPhotoUploadForm()
	{
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$this->photoUploadFormFactory->setRace($this->raceId);
		$form = $this->photoUploadFormFactory->create();		
		$form->onSuccess[] = function ($form) {
			$this->flashMessage("Fotka byla nahrána.");
			$this->redirect('this');
		};
		return $form;
	}
	
	protected function createComponentPhotoDescriptionForm()
	{	
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$photoId = $this->getParameter('photoId');
		
		$photo = $this->photoRepository->getPhoto($photoId);
		if (!$this->user->isInRole('admin') 
				&& ($photo->author->id != $this->user->id)
				&& !($this->user->isInRole('raceManager') && in_array($photo->race, $this->user->identity->data["races"]))) {
			throw new \Race\PermissionException("Nemáte požadovaná oprávnění!");
		}
		if (isset($photoId)) {
			$this->photoDescriptionFormFactory->setId((int)$photoId);
		}	
		$form = $this->photoDescriptionFormFactory->create();
		$form->onSuccess[] = function () {
			$page = $this->getParameter('page');
			$this->flashMessage("Popisek byl změněn");			
			$this->redirect('this', array("page" => $page));
		};
		return $form;
	}
	
	public function renderDefault($photoId, $page) {		
		$this->page = $this->getParameter('page');
		if ($this->paginator->itemCount === NULL) {
			$this->paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
			$this->paginator->setItemCount($this->photoRepository->countAllPublic());
			$this->paginator->setItemsPerPage(6); 
			$this->paginator->setPage($this->page);
		}		
		$this->template->photos = $this->photoRepository->getPublicPhotos($this->paginator);
		$this->template->actionPaginator = $this->actionPaginator;
		$this->template->params = $this->params;
		$this->template->paginator = $this->paginator;		
		$this->template->edit = $this->edit;
		$this->template->page = $this->page;
	}
	
	public function renderMy($photoId) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$this->page = $this->getParameter('page');
		if ($this->paginator->itemCount === NULL) {
			$this->paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
			$this->paginator->setItemCount($this->photoRepository->countAllAuthor($this->user->id));
			$this->paginator->setItemsPerPage(6); 
			$this->paginator->setPage($this->page);
		}		
		$this->template->photos = $this->photoRepository->getPhotosByAuthor($this->paginator, $this->user->id);
		$this->template->actionPaginator = "my";
		$this->template->params = $this->params;
		$this->template->paginator = $this->paginator;
		$this->template->edit = $this->edit;
		$this->template->page = $this->page;		
	}
	
	public function renderRace($race, $photoId) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$this->raceId = $race;
		$this->page = $this->getParameter('page');
		if ($this->paginator->itemCount === NULL) {
			$this->paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
			$this->paginator->setItemCount($this->photoRepository->countAllRace($this->raceId));
			$this->paginator->setItemsPerPage(6); 
			$this->paginator->setPage($this->page);
		}	
		$this->template->race = $this->raceRepository->getRace($race);
		$this->template->photos = $this->photoRepository->getPhotosByRace($this->paginator, $this->raceId);
		$this->template->actionPaginator = "race";
		$this->template->params = $this->params;
		$this->template->paginator = $this->paginator;		
		$this->template->edit = $this->edit;
		$this->template->page = $this->page;		
	}
	
	public function handleDelete($photoId, $page, $race) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}		
		$photo = $this->photoRepository->getPhoto($photoId);
		if (!$this->user->isInRole('admin') 
				&& ($photo->author->id != $this->user->id)
				&& !($this->user->isInRole('raceManager') && in_array($photo->race, $this->user->identity->data["races"]))) {
			throw new \Race\PermissionException("Nemáte požadovaná oprávnění!");
		}
		$this->page = $page;
		$this->photoRepository->deletePhoto($photoId);
		$this->flashMessage("Fotka byla smazána.");
		$this->redirect('this', array("page" => $page));	
	}
	
	public function handleEdit($photoId, $page, $race) {
		if ($this->isAjax()) {	
			if (!$this->user->isLoggedIn()) {
				throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
			}		
			$photo = $this->photoRepository->getPhoto($photoId);			
			if (!$this->user->isInRole('admin') 
					&& ($photo->author->id != $this->user->id)
					&& !($this->user->isInRole('raceManager') && in_array($photo->race, $this->user->identity->data["races"]))) {				
				throw new \Race\PermissionException("Nemáte požadovaná oprávnění!");
			}			
			$this->page = $page;				
			$this->edit = $photoId;						
			$this["photoDescriptionForm"]["description"]->setDefaultValue($photo->description);
			$this->redrawControl("photos");			
		}
	}
}
