<?php

namespace App\Forms;

use Nette,
	Nette\Application\UI\Form,
	Nette\Security\User;

/**
 * Description of ArticleFormFactory
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class ArticleFormFactory extends BaseFormFactory {
		
	private $id;
	
	public function setId($id) {
		$this->id = (int) $id;
	}
	
	public function getId() {
		return $this->id;
	}

	/**
	 * @return Form
	 */
	public function create()
	{		
		$form = new Form;
		$form->addText('title', 'Nadpis:')
			->setRequired('Je nutné vyplnit nadpis článku.');
		$form->addTextArea('lead', 'Krátký popis:', 30, 5);
		$form->addTextArea('text', 'Text článku', 60, 10)
				->setAttribute('class','mceEditor');

		$form->addCheckbox('publish', ' Ihned publikovat');

		$form->addSubmit('send', 'Uložit');

		$form->onSuccess[] = array($this, 'formSucceeded');
		
		$renderer = $form->getRenderer();
		$this->addBootstrapRendering($renderer, $form);
		
		return $form;
	}

	public function formSucceeded($form, $values)
	{		
		$status = $values->publish ? 1 : 0;
		$published = $values->publish ? date("Y-m-d H:i:s") : NULL;
		$user = $this->skautIS->usr->UserDetail()->ID;		
		$data = array(
			'status' => $status,
			'author' => $user,
			'title' => $values->title,
			'lead' => $values->lead,
			'text' => $values->text,
			'image' => 1,
			'modified' => date("Y-m-d H:i:s"),
		);
		
		if($this->id) {
			$article = $this->database->table('article')->get($this->id);
			$article->update($data);
		} else {
			$data['published'] = $published;
			$row = $this->database->table('article')->insert($data);
			$this->id = $row->id;			
		}
		
	}

}

