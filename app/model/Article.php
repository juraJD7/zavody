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
	
	private $manager;

	public function __construct(\ArticleManager $manager, $article) {		
			
		$this->manager = $manager;
		
		$this->id = $article->id;
		$this->author = $article->author;
		$this->title = $article->title;
		$this->lead = $article->lead;
		$this->text = $article->text;
		$this->image = $article->image;
		$this->status = $article->status;
		$this->modified = $article->modified;
		$this->published = $article->published;
	}
	
	public function getId() {
		if(is_int($this->id)) {
			return $this->id;
		}
		return null;
	}

	public function getAuthor() {
		if(is_int($this->author)) { //nemusim
			return $this->author;
		}
		return null;
	}

	public function setAuthor($author) {
		if(!is_int($author)) {
			throw new \Nette\MemberAccessException("Parametr author musí být integer.");
		}
		$this->author = $author;
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
		if(is_int($this->image)) {
			return $this->image;
		}
		return null;
	}

	public function setImage($image) {
		if(!is_int($image)) {
			throw new \Nette\MemberAccessException("Parametr image musí být integer.");
		}
		$this->image = $image;
	}

	public function getStatus() {
		if(is_int($this->status)) {
			return $this->status;
		}
		return null;
	}

	public function setStatus($status) {
		if(!is_int($status)) {
			throw new \Nette\MemberAccessException("Parametr status musí být integer.");
		}
		$this->status = $status;
	}

	public function getModified() {
		if($this->modified instanceof DateTime) {
			return $this->modified;
		}
		return null;
	}

	public function setModified($modified) {
		if(!($this->modified instanceof DateTime)) {
			throw new \Nette\MemberAccessException("Parametr modified musí být časový údaj.");
		}
		$this->modified = $modified;
	}

	public function getPublished() {
		if($this->published instanceof DateTime) {
			return $this->published;
		}
		return null;
	}

	public function setPublished($published) {
		if(!($this->published instanceof DateTime)) {
			throw new \Nette\MemberAccessException("Parametr modified musí být časový údaj.");
		}
		$this->published = $published;
	}

	public function getAuthorName() {
		return $this->manager->getAuthorName($this->author);
	}
	
	public function getNumComments() {
		return $this->manager->getNumComments($this->id);
	}
}
