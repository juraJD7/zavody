<?php

/**
 * Description of QuestionDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class QuestionDbMapper extends BaseDbMapper {		
	
	public function getQuestion($id) {
		$row = $this->database->table('question')->get((int)$id);
		if(!$row) {
			throw new Nette\InvalidArgumentException("Otázka $id neexistuje");
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
	
	public function getQuestions($repository, $paginator, $adminOnly, $category = null) {			
		if (!is_null($category) && !empty($category)) {
			return $this->getQuestionsByCategory($repository, $paginator, $adminOnly, $category);
		}
		$table = $this->database->table('question')
				->where('admin_only', $adminOnly)
				->where('race', NULL)
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
	
	public function getQuestionsByAuthor(QuestionRepository $repository, $paginator, $userId) {
		$table = $this->database->table('question')
				->where('author', $userId)
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
	
	public function getCatogoriesByQuestion($id) {
		$join =  $this->database->table('question')
				->get($id)
				->related('category_question');		
		$categories = array();
		foreach ($join as $category) {			
			$categories[] = $this->database->table('category')
					->where('id', $category->category_id)
					->fetch();
		}
		return $categories;
	}
	
	public function getQuestionsByCategory($repository, $paginator, $adminOnly, $id) {
		$join =  $this->database->table('category')
				->get($id)
				->related('category_question')
				->limit($paginator->getLength(), $paginator->getOffset());
		$questionIds = array();
		foreach ($join as $row) {
			$questionIds[] = $row->question_id;
		}
		$table = $this->database->table('question')
				->where('id IN', $questionIds)
				->order('changed DESC');
		$questions = array();
		foreach ($table as $row) {			
			$question = $this->getQuestion($row->id);
			$question->repository = $repository;
			if ($question->adminOnly == $adminOnly) {
				$questions[] = $question;
			}
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
	
	public function countAll($adminOnly, $category = NULL) {
		if (!is_null($category) && !empty($category)) {
			$table = $this->database->table('category_question')
					->where('category_id',$category);	
			$counter = 0;
			foreach ($table as $row) {
				$question = $this->database->table('question')
					->get($row->question_id);
				if ($question->admin_only == $adminOnly) {
					$counter++;
				}
			}
			return $counter;
		}
		return $this->database->table('question')
				->where('admin_only', $adminOnly)
				->where('race', NULL)
				->count();
	}
	
	public function countAllAuthor($userId) {		
		return $this->database->table('question')
				->where('author', $userId)
				->count();
	}	
	
	public function countAllRace($raceId) {		
		return $this->database->table('question')
				->where('race', $raceId)
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
			$answer->author = $this->userRepository->getUser($row->author);
			$answer->posted = $row->posted;
			$answer->question = $row->question;
			$answers[] = $answer;
		}		
		return $answers;
	}
	
}
