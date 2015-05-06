<?php

/**
 * Description of AdminRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class AdminRepository {
	
	private $dbMapper;	
	
	/**
	 * 
	 * @param AdminDbMapper $dbMapper
	 */
	public function __construct(AdminDbMapper $dbMapper) {
		$this->dbMapper = $dbMapper;
	}
	
	public function getAllCategories() {
		return $this->dbMapper->getAllCategories();
	}
	
	public function deleteCategory($id) {
		return $this->dbMapper->deleteCategory($id);
	}
	
	public function getAllCompetitions() {
		return $this->dbMapper->getAllCompetitions();
	}
	
	public function getAllSeasons() {
		return $this->dbMapper->getAllSeasons();
	}
	
	public function getDefaultSeason() {
		return $this->dbMapper->getDefaultSeason();
	}
}
