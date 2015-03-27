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
	
	private $manager;
	
	public function __construct(\CommentManager $manager, $comment) {
		$this->manager = $manager;
		
		$this->id = $comment->id;
		$this->article = $comment->article;
		$this->title = $comment->title;
		$this->text = $comment->text;
		$this->author = $comment->author;
		$this->posted = $comment->posted;
		$this->modified = $comment->modified;
	}
	
	public function getId() {
		if(is_int($this->id)) {
			return $this->id;
		}
		return NULL;
	}
	
	public function getArticle() {
		if(is_int($this->article)) {
			return $this->article;
		}
		return NULL;
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
		if(is_int($this->author)) {
			return $this->author;
		}
		return NULL;
	}	
	
	public function getPosted() {
		if($this->posted instanceof DateTime) {
			return $this->posted;
		}
		return NULL;
	}
	
	public function setPosted($posted) {
		if($posted instanceof DateTime) {
			$this->posted = $posted;
		} else {
			throw new InvalidArgumentException("Neplatný časový formát.");
		}
	}
	
	public function getModified() {
		if($this->modified instanceof DateTime) {
			return $this->modified;
		}
		return NULL;
	}
	
	public function setModified($modified) {
		if($modified instanceof DateTime) {
			$this->modified = $modified;
		} else {
			throw new InvalidArgumentException("Neplatný časový formát.");
		}
	}
	
	public function getAuthorName() {
		return $this->manager->getAuthorName($this->author);
	}
	
}
