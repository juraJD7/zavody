<?php

/**
 * Description of QuestionDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class QuestionDbMapper extends BaseDbMapper {
	
	private $userManager;
	
	/**
	 * 
	 * @param \Nette\Database\Context $database
	 * @param UserManager $userManager
	 */
	public function __construct(\Nette\Database\Context $database, UserManager $userManager) {
		parent::__construct($database);
		$this->userManager = $userManager;
	}
	
	public function getAllCategories() {
		$table = $this->database->table('category')
				->where('question', TRUE);
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
	
	public function getQuestion($id) {
		$row = $this->database->table('question')->get((int)$id);
		if(!$row) {
			throw new Nette\InvalidArgumentException("Otázka $id neexistuje");
		}
		$question = new Question($id);
		$question->text = $row->text;
		$question->author = $this->userManager->load($row->author);
		$question->season = $row->season;
		$question->race = $row->race;
		$question->posted = $row->posted;		
		return $question;
	}
	
	public function getQuestions($repository, $paginator, $category = null) {			
		if (!is_null($category) && !empty($category)) {
			return $this->getQuestionsByCategory($repository, $paginator, $category);
		}
		$table = $this->database->table('question')
				->order('posted DESC')
				->limit($paginator->getLength(), $paginator->getOffset());	
		$questions = array();
		foreach ($table as $row) {
			$question = $this->getQuestion($row->id);
			$question->repository = $repository;
			$questions[] = $question;
		}		
		return $questions;
	}
	
	public function getCatogoriesByQuestion($id) {
		$join =  $this->database->table('question')
				->get($id)
				->related('category_question');
		$categories = array();
		foreach ($join as $category) {			
			$categories[] = $this->database->table('category')
					->where($category->category_id)
					->fetch();
		}
		return $categories;
	}
	
	public function getQuestionsByCategory($repository,  $paginator, $id) {
		$join =  $this->database->table('category')
				->get($id)
				->related('category_question')
				->limit($paginator->getLength(), $paginator->getOffset());
		$questions = array();
		foreach ($join as $row) {			
			$question = $this->getQuestion($row->question_id);
			$question->repository = $repository;
			$questions[] = $question;
		}
		return $questions;
	}
	
	public function deleteQuestion($id) {
		$this->database->table('category_question')
				->where('question_id', $id)
				->delete();
		$this->database->table('question')
				->where('id', $id)
				->delete();
	}
	
	public function countAll($category = NULL) {
		if (!is_null($category) && !empty($category)) {
			return $this->database->table('category_question')
					->where('category_id',$category)
					->count();
		}		
		return $this->database->table('question')
				->count();
	}
	
	public function loadAnswers($id) {		
		$table = $this->database->table('answer')
				->where('question',$id)
				->order('posted');					
		$answers = array();
		foreach ($table as $row) {
			$answer = new Answer($row->id);
			$answer->text = $row->text;
			$answer->author = $this->userManager->load($row->author);
			$answer->posted = $row->posted;
			$answer->question = $row->question;
			$answers[] = $answer;
		}		
		return $answers;
	}
	
}
