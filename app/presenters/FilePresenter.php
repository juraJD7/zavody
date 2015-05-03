<?php

namespace App\Presenters;

use Nette;
	

/**
 * Description of FilePresenter
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class FilePresenter extends BasePresenter {
	
	/**
	 *
	 * @var \FileRepository
	 * @inject
	 */
	public $fileRepository;
	
	/** 
	 * @var \App\Forms\FileFormFactory
	 * @inject 
	 */
	public $fileFormFactory;
	
	/**
	 *
	 * @var \Nette\Utils\Paginator
	 * @inject
	 */
	public $paginator;
	
	private $actionPaginator;
	protected $params = array();
	private $files;
	private $category;
	
	protected function createComponentFileForm()
	{
		$form = $this->fileFormFactory->create();
		$fileId = $this->getParameter('id');
		if (isset($fileId)) {
			$this->fileFormFactory->setId((int)$fileId);
		}
		$form->onSuccess[] = function ($form) {
			$this->flashMessage("Soubor byl uložen.");			
			$link = $this->link("File:");
			$form->getPresenter()->redirectUrl($link);
		};
		return $form;
	}
	
	public function actionEdit($id) {
		try {			
			$file = $this->fileRepository->getFile($id);
			if (!($this->user->isInRole('admin') || ($this->user->id == $file->author))) {
				throw new Nette\Security\AuthenticationException("Nemáte požadovaná oprávnění!");
			}
		} catch (\InvalidArgumentException $ex) {
			$this->error($ex);
		} catch (Nette\Security\AuthenticationException $ex) {
			$this->error($ex);
			$this->redirect("File:default");
		}				
		$this['fileForm']['name']->setDefaultValue($file->name);
		$this['fileForm']['description']->setDefaultValue($file->description);
		$this['fileForm']['categories']->setDefaultValue($this->fileRepository->getCategoriesByFile($file->id));
	}
	
	public function renderUpload() {
		if ($this->user->isLoggedIn()) {
			
		} else {
			throw new Nette\Security\AuthenticationException("Nemáte oprávnění k této operaci");
		}
	}
	
	public function actionDownload($id) {
		try {			
			$file = $this->fileRepository->getFile($id);
			if (!($this->user->isInRole('admin') || ($this->user->id == $file->author))) {
				throw new Nette\Security\AuthenticationException("Nemáte požadovaná oprávnění!");
			}
		} catch (\InvalidArgumentException $ex) {
			$this->error($ex);
		} catch (Nette\Security\AuthenticationException $ex) {
			$this->error($ex);
			$this->redirect("File:default");
		}	
		$filedownload = new \FileDownloader\FileDownload();	
		$filedownload->sourceFile = \FileRepository::BASEDIR . $file->path;
		$filedownload->download();
	}
	
	public function actionDelete($id) {
		$this->fileRepository->deleteFile($id);
		$this->redirect("File:");
	}

	public function renderDefault($category) {		
		
		$page = $this->getParameter('page');
		
		if (is_null($this->category)) {
			$this->category = $this->getParameter('category');
		}
		
		if ($this->paginator->itemCount === NULL) {		
			$this->paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
			$this->paginator->setItemCount($this->fileRepository->countAll($this->category));
			$this->paginator->setItemsPerPage(10); 
			$this->paginator->setPage($page);			
		}
		
		if (is_null($this->actionPaginator)) {
			$this->actionPaginator = "default";
		}		
				
		if (is_null($this->files)) {			
			$this->files = $this->fileRepository->getFiles($this->paginator, $this->category);
		}		
		$this->params['category'] = $this->category;		
		
		$this->template->paginator = $this->paginator;
		$this->template->actionPaginator = $this->actionPaginator;
		$this->template->params = $this->params;		
		$this->template->files = $this->files;
		
		$this->template->categories = $this->fileRepository->getAllCategories();
	}
	
	public function renderMy() {		
		if ($this->user->isLoggedIn()) {
			$page = $this->getParameter('page');				
			$paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
			$paginator->setItemCount($this->fileRepository->countAllAuthor($this->user->id));
			$paginator->setItemsPerPage(10); 
			$paginator->setPage($page);			

			$this->template->paginator = $paginator;
			$this->template->actionPaginator = "my";
			$this->template->params = array();
			$this->template->files = $this->fileRepository->getFilesByAuthor($paginator, $this->user->id);	
		} else {
			throw new Nette\Security\AuthenticationException("Nemáte oprávnění k této operaci");
		}
	}
	
	public function handleChangeCategory() {
		if($this->isAjax()) {				
			$httpRequest = $this->context->getByType('Nette\Http\Request');
			$this->category = $httpRequest->getPost('category');
			$page = $this->getParameter('page');
			$this->paginator->setItemCount($this->fileRepository->countAll($this->category));			
			$this->paginator->setItemsPerPage(5); 
			$this->paginator->setPage($page); //vzdy nastavit na 1?			
			$this->actionPaginator = "default";					
			$this->files = $this->fileRepository->getFiles($this->paginator, $this->category);			
			$this->redrawControl('category');
		}			
	}
	
}
