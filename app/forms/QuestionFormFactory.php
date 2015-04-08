<?php

namespace App\Forms;

use Nette,
	Nette\Forms\Controls,
	Nette\Application\UI\Form,
	Nette\Security\User;

/**
 * Description of QuestionFormFactory
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class QuestionFormFactory extends BaseFormFactory {
	
	private $questionRepository;	
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param \Nette\Database\Context $database
	 * @param \QuestionRepository $questionRepository
	 */
	public function __construct(\Skautis\Skautis $skautIS, \Nette\Database\Context $database, \QuestionRepository $questionRepository) {
		parent::__construct($skautIS, $database);
		$this->questionRepository = $questionRepository;
	}

	/**
	 * @return Form
	 */
	public function create()
	{
		$categories = $this->questionRepository->getAllCategories();
		
		$items = array();
		foreach ($categories as $category) {
			$items[$category->id] = $category->name;
		}
		
		$form = new Form;	
		
		$form->addGroup('Nová otázka');
		
		//$form->addCheckboxList('categories', 'Zobrazovat v kategoriích', $items)
			//	->setAttribute('class', 'inline');
		
		$checkboxList = new Controls\MyCheckboxList();
		$checkboxList->setItems($items);
		
		$form->addComponent($checkboxList, 'categories');
		
		$form->addTextArea('text', 'Otázka:')
			->setRequired('Je nutné vyplnit text otázky.');	

		$form->addSubmit('send', 'Uložit');

		$form->onSuccess[] = array($this, 'formSucceeded');
		
		$renderer = $form->getRenderer();
		$this->addBootstrapRendering($renderer, $form);
		
		return $form;
	}
	
	public function formSucceeded(Form $form, $values)
	{		
		$user = $this->skautIS->usr->UserDetail()->ID;
		$data = array(
			'season' => 1,
			'text' => $values->text,
			'posted' => date("Y-m-d H:i:s"),
			'author' => $user
		);
		$question = $this->database->table('question')->insert($data);
		$this->updateCategories($values->categories, $question);					
	}	
	
	private function updateCategories($categories, $question) {		
		foreach ($categories as $category) {
			$this->database->table('category_question')
				->insert(array(
					'category_id' => $category,
					'question_id' => $question->id
				));
		}
	}
}
