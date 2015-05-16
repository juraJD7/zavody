<?php

/**
 * Competition
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class Competition extends Nette\Object {
	
	/**
	 *
	 * @var int
	 */
	private $id;
	
	/**
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 *
	 * @var string
	 */
	private $short;
	
	public function __construct($id) {
		if (!is_int($id)) {
			throw new \Nette\InvalidArgumentException('Id musí být číslo.');
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
}
