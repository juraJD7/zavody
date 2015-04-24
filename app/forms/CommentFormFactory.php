<?php

namespace App\Forms;

use Nette,
	Nette\Application\UI\Form,
	Nette\Security\User;

/**
 * Description of CommentFormFactory
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class CommentFormFactory extends BaseFormFactory {
	
	private $article;
	private $id;
	
	public function setArticle($article) {
		$this->article = (int) $article;
	}
	
	public function setId($id) {
		$this->id = $id;
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
		$form->addText('title', 'Předmět:', 20, 255)
			->setRequired('Je nutné vyplnit předmět.');
		$form->addTextArea('text', 'Text:', 40, 3);
			//	->setAttribute('class','mceEditor');		
		$form->addSubmit('send', 'Odeslat');

		$form->onSuccess[] = array($this, 'formSucceeded');
		
		$renderer = $form->getRenderer();
		$this->addBootstrapRendering($renderer, $form);
		
		return $form;
	}

	public function formSucceeded($form, $values)
	{
		$user = $this->skautIS->usr->UserDetail()->ID;		
		$data = array(			
			'author' => $user,
			'article' => $this->article,
			'title' => $values->title,			
			'text' => $values->text
		);
		
		if($this->id) {
			$article = $this->database->table('comment')->get($this->id);
			$data['modified'] = date("Y-m-d H:i:s");
			$article->update($data);
		} else {
			$data['posted'] = date("Y-m-d H:i:s");
			$data['modified'] = NULL;
			$this->database->table('comment')->insert($data);
		}		
	}
	
}
