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
	 * @var \UnitRepository
	 */
	protected $unitRepository;
	
	/**
	 * 
	 * @param \Nette\Database\Context $database
	 * @param UserRepository $userRepository
	 * @param UnitRepository $unitRepository
	 */ 
	public function __construct(\Nette\Database\Context $database, UserRepository $userRepository, UnitRepository $unitRepository) {
		
		$this->database = $database;		
		$this->userRepository = $userRepository;
		$this->unitRepository = $unitRepository;
		
	}
}
