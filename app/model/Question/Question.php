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
	private $adminOnly;
	private $categories = array();
	
	public function __construct($id) {
		if(!is_int($id)) {
			throw new \Nette\InvalidArgumentException("Parametr id musí být integer.");
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
	
	public function setAuthor(User $author) {		
		$this->author = $author;
	}
	
	public function getPosted() {		
		return $this->posted;
	}
	
	public function setPosted(DateTime $posted) {		
		$this->posted = $posted;
	}
	
	public function getRace() {
		return $this->race;
	}
	
	public function setRace($race) {
		if(!is_int($race)&&!is_null($race)) {
			throw new \Nette\InvalidArgumentException("Parametr race musí být integer.");
		}
		$this->race = $race;
	}
	
	public function getSeason() {
		return $this->season;
	}
	
	public function setSeason($season) {
		if(!is_int($season)) {
			throw new \Nette\InvalidArgumentException("Parametr season musí být integer.");
		}
		$this->season = $season;
	}
	
	public function getAnswers() {
		if (empty($this->answers)) {
			$this->answers = $this->repository->loadAnswers($this->id);
		}
		return $this->answers;
	}	
	
	public function getCategories() {
		if (empty($this->categories)) {
			$this->categories = $this->repository->getCategoriesByQuestion($this->id);
		}
		return $this->categories;
	}
	
	public function getAdminOnly() {
		return $this->adminOnly;
	}
	
	public function setAdminOnly($adminOnly) {
		if (!is_int($adminOnly)) {
			throw new Nette\InvalidArgumentException("Parametr adminOnly musí být integer.");
		}
		$this->adminOnly = $adminOnly;
	}
}
