<?php

/**
 * Description of BaseDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class BaseDbMapper {
	
	/**
	 *
	 * @var \Nette\Database\Context
	 */
	protected $database;
	
	/**
	 * 
	 * @param \Nette\Database\Context $database
	 */ 
	public function __construct(\Nette\Database\Context $database) {
		
		$this->database = $database;
		
	}
}
