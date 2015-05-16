<?php

/**
 * QuestionDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class QuestionDbMapper extends BaseDbMapper {		
	
	/**
	 * Vrátí otázku
	 * 
	 * @param int $id
	 * @return \Question
	 * @throws Race\DbNotStoredException
	 */
	public function getQuestion($id) {
		$row = $this->database->table('question')->get((int)$id);
		if(!$row) {
			throw new Race\DbNotStoredException("Otázka $id neexistuje");
		}
		$question = new Question($id);
		$question->text = $row->text;
		$question->author = $this->userRepository->getUser($row->author);
		$question->season = $row->season;
		$question->race = $row->race;
		$question->posted = $row->posted;
		$question->adminOnly = $row->admin_only;
		return $question;
	}
	
	/**
	 * Vrátí otázky podle kritérií
	 * 
	 * @param QuestionRepository $repository
	 * @param Nette\Utils\Paginator $paginator
	 * @param int $adminOnly
	 * @param int $category
	 * @return Question[]
	 */
	public function getQuestions($repository, $paginator, $adminOnly, $category = null) {			
		$table = $this->database->table('question')
				->where('admin_only', $adminOnly)
				->where('race', NULL)
				->where('season', $this->season)
				->order('changed DESC');
		if (!empty($category)) {
			$join =  $this->database->table('category_question')
					->where('category_id', $category);
			$questionIds = array();
			foreach ($join as $row) {
				$questionIds[] = $row->question_id;
			}
			$table = $table->where('id IN', $questionIds);
		}
		$table = $table->limit($paginator->getLength(), $paginator->getOffset());
		$questions = array();
		foreach ($table as $row) {
			$question = $this->getQuestion($row->id);
			$question->repository = $repository;
			$questions[] = $question;
		}		
		return $questions;
	}
	
	/**
	 * Vrátí otázky podle autora
	 * 
	 * @param QuestionRepository $repository
	 * @param Nette\Utils\Paginator $paginator
	 * @param int $userId
	 * @return Question[]
	 */
	public function getQuestionsByAuthor(QuestionRepository $repository, $paginator, $userId) {
		$table = $this->database->table('question')
				->where('author', $userId)
				->where('season', $this->season)
				->order('changed DESC')
				->limit($paginator->getLength(), $paginator->getOffset());	
		$questions = array();
		foreach ($table as $row) {
			$question = $this->getQuestion($row->id);
			$question->repository = $repository;
			$questions[] = $question;
		}
		return $questions;
	}
	
	/**
	 * Vrátí otázky týkající se závodu
	 * 
	 * @param QuestionRepository $repository
	 * @param Nette\Utils\Paginator $paginator
	 * @param int $raceId
	 * @return Question[]
	 */
	public function getQuestionsByRace(QuestionRepository $repository, $paginator, $raceId) {
		$table = $this->database->table('question')
				->where('race', $raceId)
				->order('changed DESC')
				->limit($paginator->getLength(), $paginator->getOffset());	
		$questions = array();
		foreach ($table as $row) {
			$question = $this->getQuestion($row->id);
			$question->repository = $repository;
			$questions[] = $question;
		}
		return $questions;
	}
	
	/**
	 * Vrátí pole kategorií podle otázky
	 * 
	 * @param int $id
	 * @return \Category
	 */
	public function getCategoriesByQuestion($id) {
		$join =  $this->database->table('question')
				->get($id)
				->related('category_question');		
		$categories = array();
		foreach ($join as $category) {			
			$row = $this->database->table('category')
					->where('id', $category->category_id)
					->fetch();
			$category = new Category($row->id);
			$category->name = $row->name;
			$category->short = $row->short;
			$categories[] = $category;
		}
		return $categories;
	}
	
	/**
	 * Smaže otázku
	 * 
	 * @param int $id
	 */
	public function deleteQuestion($id) {
		//smazání všech kategorií
		$this->database->table('category_question')
				->where('question_id', $id)
				->delete();
		//smazání komentářů
		$this->database->table('answer')
				->where('question', $id)
				->delete();
		//smazání otázky
		return $this->database->table('question')
				->where('id', $id)
				->delete();
	}
	
	/**
	 * Vrátí počet otázek ve zvoleném ročníku
	 * 
	 * @param int $adminOnly
	 * @param int $category
	 * @return int
	 */
	public function countAll($adminOnly, $category = NULL) {
		if (!empty($category)) {
			$table = $this->database->table('category_question')
					->where('category_id',$category);	
			$counter = 0;
			foreach ($table as $row) {
				$question = $this->database->table('question')
					->get($row->question_id);
				if ($question->admin_only == $adminOnly && $question->season == $this->season && is_null($question->race)) {
					$counter++;
				}
			}
			return $counter;
		}
		/**
		 * pokud není zadaná kategorie, sečtou se všechny záznamy pro účastníky, které nepatří
		 * k žádnému závodu a patří do aktuálního ročníku
		 */
		return $this->database->table('question')
				->where('admin_only', $adminOnly)
				->where('season', $this->season)
				->where('race', NULL)
				->count();
	}
	
	/**
	 * Vrátí počet otázek položených vybraným autorem
	 * 
	 * @param int $userId
	 * @return int
	 */
	public function countAllAuthor($userId) {		
		return $this->database->table('question')
				->where('season', $this->season)
				->where('author', $userId)
				->count();
	}	
	
	/**
	 * Vrátí počet otázek položených k vybranému závodu
	 * 
	 * @param int $raceId
	 * @return int
	 */
	public function countAllRace($raceId) {		
		return $this->database->table('question')
				->where('race', $raceId)
				->count();
	}	
	
	/**
	 * Vrátí odpovědi k otázce
	 * 
	 * @param int $id ID otázky
	 * @return \Answer[]
	 */
	public function loadAnswers($id) {		
		$table = $this->database->table('answer')
				->where('question',$id)
				->order('posted');					
		$answers = array();
		foreach ($table as $row) {
			$answer = new Answer($row->id);
			$answer->text = $row->text;
			$answer->author = $this->userRepository->getUser($row->author);
			$answer->posted = $row->posted;
			$answer->question = $row->question;
			$answers[] = $answer;
		}		
		return $answers;
	}
	
	/**
	 * Vrátí počet nezodpovězených otázek k závodu
	 * 
	 * @param int $id ID závodu
	 * @return int
	 */
	public function getNumUnansweredQuestion($id) {
		$questions = $this->database->table('question')
				->where('race', $id);
		if ($questions) {
			$counter = 0;
			foreach ($questions as $question) {
				$answear = $this->database->table('answear')
						->where('question', $question->id);
				if (!$answear) {
					$counter++;
				} 
			}
			return $counter;
		}
		return 0;
	}
	
}
