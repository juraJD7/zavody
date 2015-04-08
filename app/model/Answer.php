<?php

/**
 * Description of Answer
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Answer extends \Nette\Object {
	
	private $id;
	private $question;
	private $text;
	private $author;
	private $posted;
	
	public function __construct($id) {
		if(!is_int($id)) {
			throw new \Nette\MemberAccessException("Parametr id musí být integer.");
		}
		$this->id = $id;		
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getQuestion() {
		return $this->question;
	}
	
	public function setQuestion($question) {
		if(!is_int($question)) {
			throw new \Nette\MemberAccessException("Parametr question musí být integer.");
		}
		$this->question = $question;
	}
	
	public function getText() {
		return $this->text;
	}
	
	public function setText($text) {
		$this->text = $text;
	}
	
	public function getAuthor() {
		return $this->author;
	}
	
	public function setAuthor($author) {
		if (!($author instanceof User)) {
			throw new \Nette\MemberAccessException("Parametr author musí být typu User.");
		}
		$this->author = $author;
	}
	
	public function getPosted() {		
		return $this->posted;
	}
	
	public function setPosted($posted) {
		if(!($posted instanceof DateTime)) {
			throw new \Nette\MemberAccessException("Parametr posted musí být časový údaj.");
		}
		$this->posted = $posted;
	}
	
}	