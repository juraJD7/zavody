<?php

/**
 * AdminRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class AdminRepository {
	
	/**
	 *
	 * @var \AdminDbMapper
	 */
	private $dbMapper;	
	
	/**
	 * 
	 * @param AdminDbMapper $dbMapper
	 */
	public function __construct(AdminDbMapper $dbMapper) {
		$this->dbMapper = $dbMapper;
	}
	
	/**
	 * Vrátí všechny kategorie, nebo všechny kategorie pro zadanou agendu
	 * 
	 * @param int $type druh agendy
	 * @return \Category[]
	 */
	public function getAllCategories() {
		return $this->dbMapper->getAllCategories();
	}
	
	/**
	 * Smaže kategorii
	 * 
	 * @param int $id ID kategorie
	 */
	public function deleteCategory($id) {
		$this->dbMapper->deleteCategory($id);
	}
	
	/**
	 * Vrátí tabulku soutěží
	 * 
	 * @return \Nette\Database\Selection
	 */
	public function getAllCompetitions() {
		return $this->dbMapper->getAllCompetitions();
	}
	
	/**
	 * Vrátí pole všech ročníky v databázi
	 * 
	 * @return \Season[]
	 */
	public function getAllSeasons() {
		return $this->dbMapper->getAllSeasons();
	}
	
	/**
	 * 
	 * @return int ID výchozího ročníku
	 */
	public function getDefaultSeason() {
		return $this->dbMapper->getDefaultSeason();
	}
}
