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
	 * @var \UserManager
	 */
	protected $userManager;
	
	/**
	 * 
	 * @param \Nette\Database\Context $database
	 * @param UnitRepository $unitRepository
	 * @param UserManager $userManager
	 */ 
	public function __construct(\Nette\Database\Context $database, UserManager $userManager) {
		
		$this->database = $database;
		$this->userManager = $userManager;
		
	}
}
