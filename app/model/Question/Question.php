<?php

/**
 * Description of Question
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Question extends \Nette\Object {
	
	private $repository;
	
	private $id;
	private $text;
	private $author;
	private $posted;
	private $race;
	private $season;
	private $answers;
	
	public function __construct($id) {
		if(!is_int($id)) {
			throw new \Nette\MemberAccessException("Parametr id musí být integer.");
		}
		$this->id = $id;		
	}
	
	public function setRepository(QuestionRepository $repository) {
		$this->repository = $repository;
	}
	
	public function getId() {
		return $this->id;
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
	
	public function getRace() {
		return $this->race;
	}
	
	public function setRace($race) {
		if(!is_int($race)&&!is_null($race)) {
			throw new \Nette\MemberAccessException("Parametr race musí být integer.");
		}
		$this->race = $race;
	}
	
	public function getSeason() {
		return $this->season;
	}
	
	public function setSeason($season) {
		if(!is_int($season)) {
			throw new \Nette\MemberAccessException("Parametr season musí být integer.");
		}
		$this->season = $season;
	}
	
	public function getAnswers() {
		if (empty($this->answers)) {
			$this->answers = $this->repository->loadAnswers($this->id);
		}
		return $this->answers;
	}
	
}
