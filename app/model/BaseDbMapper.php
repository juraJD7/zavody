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
		if (isset($_COOKIE["season"])) {
			$this->season = $_COOKIE["season"];
		} else {
			$this->season = $this->database->table('setting')->get('season')->value;					
		}
		$this->competition = $this->database->table('season')
				->get($this->season)
				->competition;
		
	}
	
	/**
	 * Vrátí všechny kategorie, nebo všechny kategorie pro zadanou agendu
	 * 
	 * @param int $type druh agendy
	 * @return \Category[]
	 */
	public function getAllCategories($type = NULL) {
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
	
	/**
	 * Smaže kategorii
	 * 
	 * @param int $id
	 */
	public function deleteCategory($id) {
		//zrušení kategorie ve všech agendách
		$this->database->table('article_category')
			->where('category_id', $id)
			->delete();
		$this->database->table('category_file')
			->where('category_id', $id)
			->delete();
		$this->database->table('category_question')
			->where('category_id', $id)
			->delete();
		//smazání kategorie
		$this->database->table('category')
			->where('id', $id)
			->delete();
	}
}
