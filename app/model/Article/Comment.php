<?php

/**
 * Description of Comment
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Comment extends Nette\Object {
	
	private $id;
	private $article;
	private $title;
	private $text;
	private $author;
	private $posted;
	private $modified;
	
	private $repository;
	
	public function __construct($id) {
		$this->id = $id;
	}
	
	public function getId() {
		return $this->id;
	}

	public function setRepository($repository) {
		$this->repository = $repository;
	}	

	public function getArticle() {
		$this->article;
	}
	
	public function setArticle($article) {
		$this->article = $article;
	}

	public function getTitle() {
		return $this->title;
	}
	
	public function setTitle($title) {
		$this->title = $title;
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
	
	public function getModified() {
		return $this->modified;
	}
	
	public function setModified(DateTime $modified = NULL) {		
		$this->modified = $modified;		
	}	
}
