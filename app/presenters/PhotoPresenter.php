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
	 * @var \Nette\Utils\Paginator
	 * @inject
	 */
	public $paginator;
	
	private $actionPaginator;
	protected $params = array();
	private $edit;
	private $page;
	
	protected function createComponentPhotoUploadForm()
	{
		$form = $this->photoUploadFormFactory->create();		
		$form->onSuccess[] = function ($form) {
			$this->flashMessage("Fotka byla nahrána.");			
			$link = $this->link("Photo:");
			$form->getPresenter()->redirectUrl($link);
		};
		return $form;
	}
	
	protected function createComponentPhotoDescriptionForm()
	{		
		$photoId = $this->getParameter('id');		
		if (isset($photoId)) {
			$this->photoDescriptionFormFactory->setId((int)$photoId);
		}	
		$form = $this->photoDescriptionFormFactory->create();
		$form->onSuccess[] = function ($form) {
			$this->flashMessage("Popisek byl změněn");			
			$link = $this->link("Photo:");
			$form->getPresenter()->redirectUrl($link);
		};
		return $form;
	}
	
	public function renderDefault() {
		
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
	
	public function renderMy() {
		if ($this->user->isLoggedIn()) {
			$this->page = $this->getParameter('page');
			if ($this->paginator->itemCount === NULL) {
				$this->paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
				$this->paginator->setItemCount($this->photoRepository->countAllAuthor($this->user->id));
				$this->paginator->setItemsPerPage(6); 
				$this->paginator->setPage($this->page);
			}		
			$this->template->photos = $this->photoRepository->getPhotosByAuthor($this->paginator, $this->user->id);
			$this->template->actionPaginator = $this->actionPaginator;
			$this->template->params = $this->params;
			$this->template->paginator = $this->paginator;
			$this->template->edit = $this->edit;
			$this->template->page = $this->page;
		} else {
			throw new Nette\Security\AuthenticationException("Nemáte oprávnění k této operaci");
		}
	}
	
	public function handleDelete($id, $page) {
		$photo = $this->photoRepository->getPhoto($id);
		if ($this->user->isInRole('admin') || $photo->author->id == $this->user->id) {
			$this->page = $page;
			$this->photoRepository->deletePhoto($id);
			$this->redrawControl("photos");
		} else {
			throw new Nette\Security\AuthenticationException("Nemáte potřebná oprávnění");
		}
	}
	
	public function handleEdit($id, $page) {
		if ($this->isAjax()) {			
			$photo = $this->photoRepository->getPhoto($id);
			if ($this->user->isInRole('admin') || $photo->author->id == $this->user->id) {
				$this->page = $page;				
				$this->edit = $id;			
				$this->template->photos = $this->photoRepository->getPublicPhotos($this->paginator);
				$this["photoDescriptionForm"]["description"]->setDefaultValue($photo->description);
				$this->redrawControl("photos");
			} else {
				throw new Nette\Security\AuthenticationException("Nemáte potřebná oprávnění");
			}
		}
	}
}
