<?php

/**
 * Description of Category
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Category extends \Nette\Object {
	
	private $id;
	private $name;
	private $short;
	private $description;
	
	public function __construct($id) {
		if(!is_int($id)) {
			throw new \Nette\MemberAccessException("Parametr id musí být integer.");
		}
		$this->id = $id;		
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}
	
	public function getShort() {
		return $this->short;
	}
	
	public function setShort($short) {
		$this->short = $short;
	}
	
	public function getDescription() {
		return $this->description;
	}
	
	public function setDescription($description) {
		$this->description = $description;
	}
}
