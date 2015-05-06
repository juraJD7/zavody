<?php

namespace App\Presenters;

use Nette,
	Nette\Application\UI\Form;

/**
 * Description of AdminPresenter
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class AdminPresenter extends BasePresenter {
	
	/**
	 *
	 * @var \FileRepository
	 * @inject
	 */
	public $fileRepository;

	/**
	 *
	 * @var \ArticleRepository
	 * @inject
	 */
	public $articleRepository;
	
	public function createComponentAddAdminForm() {
		$form = new Form();
		
		$users = $this->userRepository->loadNonAdminUsers();
		$items = array();
		foreach ($users as $user) {
			$items[$user->id] = $user->displayName;
		}
		$form->addGroup("Přídání nových administrátorů");
		$form->addMultiSelect('admin', 'Nový admin: ', $items)				
				->setAttribute('class', 'js-example-basic-multiple');				
		$form->addSubmit('send', "Odeslat");
		$form->onSuccess[] = array($this, 'addAdminFormSucceeded');
		return $form;
	}
	
	public function addAdminFormSucceeded(Form $form, $values) {
		foreach ($values->admin as $admin) {
			$this->database->table('user')
					->where('id_user', $admin)
					->update(array('is_admin' => TRUE));
		}
		$this->flashMessage("Administrátoři byli nastaveni.");
		$this->redirect('this');
	}	
	
	public function createComponentWhiteListForm() {
		$form = new Form();
		
		
		$form->addGroup("Přídání nového typu souboru");
		$form->addText('title', "Titulek");
		$form->addText('mime', "MIME type");
		$form->addUpload('icon', 'Ikona:');
		$form->addSubmit('send', "Odeslat");
		$form->onSuccess[] = array($this, 'whiteListFormSucceeded');
		return $form;
	}
	
	public function whiteListFormSucceeded(Form $form, $values) {
		$icon = $values->icon;
		if($icon->isOk() && $icon->getContentType() == "image/png") {			
			$path = str_replace("/", "-", $values->mime) . ".png";		
			$icon->move("./" . \File::ICONDIR . $path);
		} else {
			$path = "default.png";
		}
		$this->database->table('whitelist')
				->insert(array(
					'title' => $values->title,
					'mime' => $values->mime,
					'path' => $path
				));

		$this->flashMessage("Druh souboru byl přidán.");
		$this->redirect('this');
	}	
	
	public function renderDefault() {
		if ($this->user->isInRole('admin')) {
			$this->template->admins = $this->userRepository->getAdmins();
		} else {
			throw new Nette\Security\AuthenticationException("Nemáte oprávnění k této akci");
		}
	}
	
	public function handleDeleteAdmin($id) {
		$user = $this->userRepository->getUser($id);
		$user->admin = FALSE;
		$user->save();
		$this->redrawControl();
	}
	
	public function renderWhitelist() {
		if ($this->user->isInRole('admin')) {
			$this->template->filetypes = $this->fileRepository->getFileTypes();
		} else {
			throw new Nette\Security\AuthenticationException("Nemáte oprávnění k této akci");
		}
	}
	
	public function handleDeleteFileType($mime) {
		$this->fileRepository->deleteFileType($mime);
		$this->flashMessage("Druh souboru byl odebrán.");
		$this->redrawControl();
	}
	
	public function renderCategories() {
		if ($this->user->isInRole('admin')) {
			$this->template->categories = $this->articleRepository->getAllCategories();
		} else {
			throw new Nette\Security\AuthenticationException("Nemáte oprávnění k této akci");
		}
	}
}
