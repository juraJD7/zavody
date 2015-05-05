<?php

/**
 * Description of ArticleDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class ArticleDbMapper extends BaseDbMapper {
	
	/**
	 * 
	 * @param int $id
	 * @return \Article
	 * @throws Nette\InvalidArgumentException
	 */
	public function getArticle($id) {
		$row = $this->database->table('article')->get($id);
		if(!$row) {
			throw new Nette\InvalidArgumentException("Článek $id neexistuje");
		}
		$article =  new Article($row->id);				
		
		$article->author = $this->userRepository->getUser($row->author);
		$article->title = $row->title;
		$article->lead = $row->lead;
		$article->text = $row->text;
		$article->image = $row->image;
		$article->status = $row->status;
		$article->modified = $row->modified;
		$article->published = $row->published;
		$article->race = $row->race;
		$article->season = $row->season;
		$article->adminOnly = $row->admin_only;
		
		return $article;
	}	
	
	public function getArticles(Nette\Utils\Paginator $paginator, ArticleRepository $repository, $adminOnly, $category) {
		if (!is_null($category) && !empty($category)) {
			return $this->getArticlesByCategory($repository, $paginator, $adminOnly, $category);
		}		
		$rows = $this->database->table('article')
				->where('admin_only', $adminOnly)
				->where('race', NULL)
				->where('season', $this->season)
				->where('status', Article::PUBLISHED)
				->order('modified DESC')
				->limit($paginator->getLength(), $paginator->getOffset());	
		$articles = array();
		foreach ($rows as $row) {
			$article = $this->getArticle($row->id);
			$article->repository = $repository;
			$articles[] = $article; 
		}
		return $articles;
	}
	
	public function getArticlesByCategory($repository, $paginator, $adminOnly, $id) {
		$join =  $this->database->table('article_category')
				->where('category_id', $id);
				
		$articleIds = array();
		foreach ($join as $row) {
			$articleIds[] = $row->article_id;
		}
		$table = $this->database->table('article')
				->where('id IN', $articleIds)
				->where('season', $this->season)
				->order('modified DESC')
				->limit($paginator->getLength(), $paginator->getOffset());
		$articles = array();
		foreach ($table as $row) {			
			$article = $this->getArticle($row->id);
			$article->repository = $repository;
			if ($article->adminOnly == $adminOnly) {
				$articles[] = $article;
			}
		}
		return $articles;
	}
	
	public function getCatogoriesByArticle($id) {
		$join =  $this->database->table('article_category')
					->where('article_id', $id);
		$categories = array();
		foreach ($join as $category) {				
			$row = $this->database->table('category')
					->where('id', $category->category_id)
					->fetch();
			$category = new Category($row->id);
			$category->name = $row->name;
			$category->short = $row->short;
			$category->description = $row->description;
			$categories[] = $category;
		}
		return $categories;
	}
	
	/**
	 * 
	 * @param int $category
	 * @return int
	 */
	public function countAll($adminOnly, $category = NULL) {
		if (!is_null($category) && !empty($category)) {
			$table = $this->database->table('article_category')
					->where('category_id',$category);	
			$counter = 0;
			foreach ($table as $row) {
				$article = $this->database->table('article')
					->where('status', Article::PUBLISHED)					
					->get($row->article_id);
				if ($article->admin_only == $adminOnly && $article->season == $this->season) {
					$counter++;
				}
			}
			return $counter;
		}
		return $this->database->table('article')
				->where('admin_only', $adminOnly)
				->where('race', NULL)
				->where('season', $this->season)
				->where('status', Article::PUBLISHED)
				->count();
	}
	
	
	public function countAllAuthor($userId) {
		$rows = $this->database->table('article')
				->where('season', $this->season)
				->where('author', $userId);
		return $rows->count();
	}
	
	public function countAllRace($raceId) {
		$rows = $this->database->table('article')
				->where('race', $raceId)
				->where('status', Article::PUBLISHED);
		return $rows->count();
	}
	
	/**
	 * 
	 * @param int $id ID řádku
	 * @return int id smazaného řádku?
	 */
	public function delete($id) {
		$this->database->table('comment')
				->where('article',$id)
				->delete();
		return $this->database->table('article')
				->get($id)->
				delete();
	}
	
	public function getArticlesByAuthor(ArticleRepository $repository, $paginator, $userId) {
		$rows = $this->database->table('article')
				->where('author', $userId)
				->where('season', $this->season)
				->order('modified DESC')
				->limit($paginator->getLength(), $paginator->getOffset());
		$articles = array();
		foreach ($rows as $row) {
			$article = $this->getArticle($row->id);
			$article->repository = $repository;
			$articles[] = $article; 
		}
		return $articles;
	}
	
	public function getArticlesByRace(ArticleRepository $repository, $paginator, $raceId) {
		$rows = $this->database->table('article')
				->where('race', $raceId)
				->order('modified DESC')
				->limit($paginator->getLength(), $paginator->getOffset());
		$articles = array();
		foreach ($rows as $row) {
			$article = $this->getArticle($row->id);
			$article->repository = $repository;
			$articles[] = $article; 
		}
		return $articles;
	}
}
