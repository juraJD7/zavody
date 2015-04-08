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
	
	public function getQuestions($paginator, $category = NULL) {
		return $this->dbMapper->getQuestions($this, $paginator, $category);
	}
	
	public function getCategoriesByQuestion($id) {
		return $this->dbMapper->getCatogoriesByQuestion($id);
	}
	
	public function getAllCategories() {
		return $this->dbMapper->getAllCategories();
	}
	
	public function deleteQuestion($id) {
		$this->dbMapper->deleteQuestion($id);
	}
	
	public function countAll($category = NULL) {
		return $this->dbMapper->countAll($category);
	}
	
	public function loadAnswers($id) {
		return $this->dbMapper->loadAnswers($id);
	}
}
