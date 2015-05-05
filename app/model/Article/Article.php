<?php

/**
 * Description of Article
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Article extends Nette\Object {
	
	const STORED = 0;
	const PUBLISHED = 1;

	private $id;
	private $author;
	private $title;
	private $lead;
	private $text;
	private $image;
	private $status;
	private $modified;
	private $published;
	private $season;
	private $race;
	private $adminOnly;
	
	private $repository;

	public function __construct($id) {
		$this->id = $id;
	}
	
	public function setRepository($repository) {
		$this->repository = $repository;
	}

	public function getId() {
		return $this->id;		
	}

	public function getAuthor() {		
		return $this->author;
	}
	
	public function setAuthor(User $user) {		
		$this->author = $user;
	}

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getLead() {
		return $this->lead;
	}

	public function setLead($lead) {
		$this->lead = $lead;
	}

	public function getText() {
		return $this->text;
	}

	public function setText($text) {
		$this->text = $text;
	}

	public function getImage() {		
		return $this->image;		
	}

	public function setImage($image) {
		if(!is_int($image)) {
			throw new \Nette\MemberAccessException("Parametr image musí být integer.");
		}
		$this->image = $image;
	}

	public function getStatus() {
		return $this->status;
	}

	public function setStatus($status) {
		if(!is_int($status)) {
			throw new \Nette\MemberAccessException("Parametr status musí být integer.");
		}
		$this->status = $status;
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
			throw new \Nette\InvalidArgumentException("Parametr season musí být integer.");
		}
		$this->season = $season;
	}

	public function getModified() {
		return $this->modified;
	}

	public function setModified(DateTime $modified) {
		$this->modified = $modified;
	}

	public function getPublished() {
		return $this->published;
	}

	public function setPublished(DateTime $published = NULL) {
		$this->published = $published;
	}
	
	public function getNumComments() {
		return $this->repository->getNumComments($this->id);
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
