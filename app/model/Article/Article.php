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
}
