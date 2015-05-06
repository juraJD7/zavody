<?php

/**
 * Description of Category
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Category extends \Nette\Object {
	
	private $id;
	private $name;
	private $short;
	private $article;
	private $file;
	private $question;
	
	public function __construct($id) {
		if(!is_int($id)) {
			throw new \Nette\MemberAccessException("Parametr id musí být integer.");
		}
		$this->id = $id;		
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}
	
	public function getShort() {
		return $this->short;
	}
	
	public function setShort($short) {
		$this->short = $short;
	}
	
	public function isArticle() {
		if ($this->article) {
			return "Ano";
		}
		return "Ne";
	}
	
	public function setArticle($article) {
		if ($article) {
			$this->article = TRUE;
		} else {
			$this->article = FALSE;
		}
		
	}
	
	public function isFile() {
		if ($this->file) {
			return "Ano";
		}
		return "Ne";
	}
	
	public function setFile($file) {
		if ($file) {
			$this->file = TRUE;
		} else {
			$this->file = FALSE;
		}
		
	}
	
	public function isQuestion() {
		if ($this->question) {
			return "Ano";
		}
		return "Ne";
	}
	
	public function setQuestion($question) {
		if ($question) {
			$this->question = TRUE;
		} else {
			$this->question = FALSE;
		}
		
	}
}
