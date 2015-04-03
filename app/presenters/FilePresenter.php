<?php

namespace App\Presenters;

use Nette,
	\App\Forms\ArticleFormFactory,
	\App\Forms\CommentFormFactory;


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
	}
	
	public function renderUpload() {
		if ($this->user->isLoggedIn()) {
			
		} else {
			throw new Nette\Security\AuthenticationException("Nemáte oprávnění k této operaci");
		}
	}

	public function renderDefault() {		
		$this->template->files = $this->fileRepository->getFiles();	
		$this->template->message = "mess";
	}
	
}
