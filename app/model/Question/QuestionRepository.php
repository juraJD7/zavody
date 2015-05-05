<?php

/**
 * Description of QuestionRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class QuestionRepository {
	
	private $dbMapper;
	
	/**
	 * 
	 * @param QuestionDbMapper $dbMapper
	 */
	public function __construct(QuestionDbMapper $dbMapper) {
		$this->dbMapper = $dbMapper;
	}
	
	public function getQuestion($id) {	
		$file = $this->dbMapper->getQuestion($id);
		$file->repository = $this;
		return $file;
	}
	
	public function getQuestions($paginator, $adminOnly = 0, $category = NULL) {
		return $this->dbMapper->getQuestions($this, $paginator, $adminOnly, $category);
	}
	
	public function getQuestionsByAuthor($paginator, $userId) {
		return $this->dbMapper->getQuestionsByAuthor($this, $paginator, $userId);
	}
	
	public function getQuestionsByRace($paginator, $raceId) {
		return $this->dbMapper->getQuestionsByRace($this, $paginator, $raceId);
	}
	
	public function getCategoriesByQuestion($id) {
		return $this->dbMapper->getCatogoriesByQuestion($id);
	}
	
	public function getAllCategories($type) {
		return $this->dbMapper->getAllCategories($type);
	}
	
	public function deleteQuestion($id) {
		$this->dbMapper->deleteQuestion($id);
	}
	
	public function countAll($adminOnly = 0, $category = NULL) {
		return $this->dbMapper->countAll($adminOnly, $category);
	}
	
	public function countAllAuthor($userId) {
		return $this->dbMapper->countAllAuthor($userId);
	}
	
	public function countAllRace($raceId) {
		return $this->dbMapper->countAllRace($raceId);
	}
	
	public function loadAnswers($id) {
		return $this->dbMapper->loadAnswers($id);
	}
}
