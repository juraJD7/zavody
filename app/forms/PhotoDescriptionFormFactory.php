<?php

namespace App\Forms;

use Nette,
	Nette\Application\UI\Form,
	Nette\Security\User;

/**
 * Description of PhotoDescriptionFactory
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PhotoDescriptionFormFactory extends BaseFormFactory {
	
	private $photoRepository;
	
	private $id;
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param \Nette\Database\Context $database
	 * @param \PhotoRepository $photoRepository
	 */
	public function __construct(\Skautis\Skautis $skautIS, \Nette\Database\Context $database, \PhotoRepository $photoRepository) {
		parent::__construct($skautIS, $database);
		$this->photoRepository = $photoRepository;
	}
	
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * @return Form
	 */
	public function create()
	{
		$form = new Form;

		$form->addText('description')
				->setAttribute('class', 'form-control');
		$form->addHidden('id',  $this->id);
		$form->addSubmit('send', 'Uložit');

		$form->onSuccess[] = array($this, 'formSucceeded');
		
		$renderer = $form->getRenderer();
		$this->addBootstrapRendering($renderer, $form);
		
		return $form;
	}
	
	public function formSucceeded(Form $form, $values)
	{		
		$photo = $this->database->table('photo')->get($values->id);
		$photo->update(array(
			"description" => $values->description
		));			
	}
}
