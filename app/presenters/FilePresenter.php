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
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$form = $this->fileFormFactory->create();
		$fileId = $this->getParameter('id');
		if (isset($fileId)) {
			$this->fileFormFactory->setId((int)$fileId);
		}
		$form->onSuccess[] = function () {
			$this->flashMessage("Soubor byl uložen.");
			$this->redirect('this');
		};
		return $form;
	}
	
	public function actionEdit($id) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}		
		$file = $this->fileRepository->getFile($id);
		if (!($this->user->isInRole('admin') || ($this->user->id == $file->author))) {
			throw new \Race\PermissionException("Nemáte požadovaná oprávnění!");
		}		
		$this['fileForm']['name']->setDefaultValue($file->name);
		$this['fileForm']['description']->setDefaultValue($file->description);
		$categories = $this->fileRepository->getCategoriesByFile($file->id);			
		$items = array();
		foreach ($categories as $category) {
			$items[] = $category->id;
		}
		$this['fileForm']['categories']->setDefaultValue($items);
	}
	
	public function renderUpload() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
	}
	
	public function actionDownload($id) {		
		$file = $this->fileRepository->getFile($id);				
		$filedownload = new \FileDownloader\FileDownload();	
		$filedownload->sourceFile = \FileRepository::BASEDIR . $file->path;
		$filedownload->download();
	}
	
	public function actionDelete($id) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$file = $this->fileRepository->getFile($id);
		if (!($this->user->isInRole('admin') || ($this->user->id == $file->author))) {
			throw new \Race\PermissionException("Nemáte požadovaná oprávnění!");
		}
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
		
		$this->template->categories = $this->fileRepository->getAllCategories('file');
	}
	
	public function renderMy() {		
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$page = $this->getParameter('page');				
		$paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
		$paginator->setItemCount($this->fileRepository->countAllAuthor($this->user->id));
		$paginator->setItemsPerPage(10); 
		$paginator->setPage($page);			

		$this->template->paginator = $paginator;
		$this->template->actionPaginator = "my";
		$this->template->params = array();
		$this->template->files = $this->fileRepository->getFilesByAuthor($paginator, $this->user->id);
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
