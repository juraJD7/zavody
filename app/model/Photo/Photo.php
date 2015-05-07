<?php

/**
 * Description of Photo
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Photo extends \Nette\Object {	
	
	private $repository;
	
	private $id;
	private $description;
	private $race;
	private $public;
	private $author;
	private $size;
	private $height;
	private $width;
	private $created;
	private $type;
	
	private $path;
	private $thumbPath;
	
	public function __construct($id) {
		if(!is_int($id)) {
			throw new \Nette\InvalidArgumentException("Parametr id musí být integer.");
		}
		$this->id = $id;		
	}
	
	public function setRepository(PhotoRepository $repository) {
		$this->repository = $repository;
	}

	public function getId() {
		return $this->id;
	}	
	
	public function getDescription() {
		return $this->description;
	}
	
	public function setDescription($description) {
		$this->description = $description;
	}
	
	public function getRace() {
		return $this->race;
	}
	
	public function setRace($race) {
		if (!is_int($race) && !is_null($race)) {
			throw new \Nette\InvalidArgumentException("Parametr race musí být integer.");
		}
		$this->race = $race;
	}
	
	public function isPublic() {
		return $this->isPublic;
	}
	
	public function setPublic($public) {
		if($public) {
			$this->public = TRUE;
		}
		$this->public = FALSE;
	}
	
	public function getHeight() {
		return $this->height;
	}
	
	public function setHeight($height) {
		if (!is_int($height)) {
			throw new \Nette\InvalidArgumentException("Parametr height musí být integer.");
		}
		$this->height = $height;
	}
	
	public function getWidth() {
		return $this->width;
	}
	
	public function setWidth($width) {
		if (!is_int($width)) {
			throw new \Nette\InvalidArgumentException("Parametr width musí být integer.");
		}
		$this->width = $width;
	}
	
	public function getSize() {
		return $this->size;
	}
	
	public function setSize($size) {
		if (!is_int($size)) {
			throw new \Nette\InvalidArgumentException("Parametr size musí být integer.");
		}
		$this->size = $size;
	}
	
	public function getAuthor() {
		return $this->author;
	}
	
	public function setAuthor(User $author) {		
		$this->author = $author;
	}
	
	public function getCreated() {
		return $this->created;
	}

	public function setCreated(DateTime $created) {		
		$this->created = $created;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function setType($type) {
		$this->type = $type;
	}

	public function getPath() {
		if (is_null($this->path)) {
			$this->path = $this->repository->getPath($this->id, $this->path);
		}
		return $this->path;
	}
	
	public function getThumbPath() {
		if (is_null($this->thumbPath)) {
			$this->thumbPath = $this->repository->getThumbPath($this->id);
		}
		return $this->thumbPath;
	}
}
