<?php

/**
 * Description of File
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class File extends Nette\Object {
	
	const MB = 1048576;
	const kB = 1024;
	
	private $repository;
	
	private $id;
	private $path;
	private $name;
	private $descrtiption;
	private $type;
	private $size;
	private $author;
	
	private $iconPath;
	private $categories;
	
	public function __construct($id) {
		if(!is_int($id)) {
			throw new \Nette\MemberAccessException("Parametr id musí být integer.");
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
		return $this->descrtiption;
	}
	
	public function setDescription($description) {
		$this->descrtiption = $description;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function getSize() {
		return $this->id;
	}
	
	public function setSize($size) {
		if (!is_int($size)) {
			throw new \Nette\MemberAccessException("Parametr size musí být integer.");
		}
		$this->size = $size;
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
	
	public function getFormattedSize() {		
		if ($this->size > File::MB) { 
			return ($this->size / File::MB) . " MB";
		}
		if ($this->size > File::kB) { 
			return ($this->size / File::kB) . " kB";
		}
		return $this->size . " B";
	}
	
	public function getIconPath() {
		if (!isset($this->iconPath)) {
			$this->iconPath = $this->repository->getIconPath($this->type);
		}
		return $this->iconPath;
	}
	
	public function getCategories() {
		if (empty($this->categories)) {
			$this->categories = $this->repository->getCategoriesByFile($this->id);
		}
		return $this->categories;
	}
}
