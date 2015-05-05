<?php

/**
 * Description of BaseDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class BaseDbMapper {
	
	const ADMIN_ONLY = 1;
	const COMMON = 0;
	
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
	
	public function getAllCategories($type) {
		$table = $this->database->table('category')
				->where($type, TRUE);
		$categories = array();
		foreach ($table as $row) {
			$category = new Category($row->id);
			$category->name = $row->name;
			$category->short = $row->short;
			$category->description = $row->description;
			$categories[] = $category;
		}
		return $categories;
	}
}
