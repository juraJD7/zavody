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
	
	protected $season;
	
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
		$this->season = $_COOKIE["season"];
		$this->competition = $this->database->table('season')
				->get($this->season)
				->competition;
		
	}
	
	public function getAllCategories($type) {
		$table = $this->database->table('category');
		if (!is_null($type)) {
			$table = $table->where($type, TRUE);
		}
		$categories = array();
		foreach ($table as $row) {
			$category = new Category($row->id);
			$category->name = $row->name;
			$category->short = $row->short;			
			$category->article = $row->article;
			$category->file = $row->file;
			$category->question = $row->question;
			$categories[] = $category;
		}
		return $categories;
	}
}
