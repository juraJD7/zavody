<?php

/**
 * Description of File
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class File extends Nette\Object {
	
	const MB = 1048576;
	const kB = 1024;
	
	const ICONDIR = "/img/icons/";
	
	private $repository;
	
	private $id;
	private $path;
	private $name;
	private $description;
	private $type;
	private $size;
	private $author;
	private $competition;
	
	private $iconPath;
	private $categories;
	
	public function __construct($id) {
		if(!is_int($id)) {
			throw new \Nette\InvalidArgumentException("Parametr id musí být integer.");
		}
		$this->id = $id;		
	}
	
	public function setRepository(FileRepository $repository) {
		$this->repository = $repository;
	}

	public function getId() {
		return $this->id;
	}
	
	public function getPath() {
		return $this->path;
	}
	
	public function setPath($path) {
		$this->path = $path;
	}

	public function getName() {
		return $this->name;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getDescription() {
		return $this->description;
	}
	
	public function setDescription($description) {
		$this->description = $description;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function setType(FileType $type) {
		$this->type = $type;
	}
	
	public function getCompetition() {
		return $this->competition;
	}
	
	public function setCompetition($competition) {
		if(!is_int($competition)) {
			throw new \Nette\InvalidArgumentException("Parametr season musí být integer.");
		}
		$this->competition = $competition;
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
	
	public function getFormattedSize() {		
		if ($this->size > File::MB) { 
			return round($this->size / File::MB, 1) . " MB";
		}
		if ($this->size > File::kB) { 
			return round($this->size / File::kB, 1) . " kB";
		}
		return $this->size . " B";
	}	
	
	public function getCategories() {
		if (empty($this->categories)) {
			$this->categories = $this->repository->getCategoriesByFile($this->id);
		}
		return $this->categories;
	}
}
