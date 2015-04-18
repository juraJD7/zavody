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
	 * @var \UserRepository
	 */
	protected $userRepository;
	
	/**
	 * 
	 * @param \Nette\Database\Context $database
	 * @param UnitRepository $unitRepository
	 * @param UserRepository $userRepository
	 */ 
	public function __construct(\Nette\Database\Context $database, UserRepository $userRepository) {
		
		$this->database = $database;		
		$this->userRepository = $userRepository;
		
	}
}
