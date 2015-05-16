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
			throw new Race\DbNotStoredException("Článek $id neexistuje");
		}
		$article =  new Article($row->id);				
		
		$article->author = $this->userRepository->getUser($row->author);
		$article->title = $row->title;
		$article->lead = $row->lead;
		$article->text = $row->text;
		$article->status = $row->status;
		$article->modified = $row->modified;
		$article->published = $row->published;
		$article->race = $row->race;
		$article->season = $row->season;
		$article->adminOnly = $row->admin_only;
		
		return $article;
	}	
	
	/**
	 * Vrátí pole článků, které nepatří k závodu
	 * 
	 * @param Nette\Utils\Paginator $paginator omezí výběr na stránku
	 * @param ArticleRepository $repository
	 * @param int $adminOnly TRUE vrátí články pro administrátory, FALSE pro účastníky
	 * @param int $category omezí výběr na kategorii
	 * @return \Article[]
	 */
	public function getArticles(Nette\Utils\Paginator $paginator, ArticleRepository $repository, $adminOnly, $category) {			
		// výběr publikovaných článků bez vazby na závod z aktuálního ročníku
		$rows = $this->database->table('article')
				->where('admin_only', $adminOnly)
				->where('race', NULL)
				->where('season', $this->season)
				->where('status', Article::PUBLISHED)
				->order('changed DESC');
		// pokud je zadána kategorie
		if (!empty($category)) {
			$join =  $this->database->table('article_category')
				->where('category_id', $category);				
			$articleIds = array();
			foreach ($join as $row) {
				$articleIds[] = $row->article_id;
			}
			$rows = $rows->where('id IN', $articleIds);
		}
		//omezení stránkováním
		$rows = $rows->limit($paginator->getLength(), $paginator->getOffset());	
		$articles = array();
		foreach ($rows as $row) {
			$article = $this->getArticle($row->id);
			$article->repository = $repository;
			$articles[] = $article; 
		}
		return $articles;
	}
	
	/**
	 * Vrátí kategorie článku
	 * 
	 * @param int $id
	 * @return \Category[]
	 */
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
			$categories[] = $category;
		}
		return $categories;
	}
	
	/**
	 * Vrátí počet článků
	 * 
	 * @param int $adminOnly TRUE počítá článka pro organizátory, FALSE pro účastníky
	 * @param int $category omezení na kategorie
	 * @return int
	 */
	public function countAll($adminOnly, $category = NULL) {
		if (!empty($category)) {
			$table = $this->database->table('article_category')
					->where('category_id', $category);	
			$counter = 0;
			foreach ($table as $row) {
				$article = $this->database->table('article')									
					->get($row->article_id);
				if ($article->admin_only == $adminOnly && $article->season == $this->season
						&& $article->status == Article::PUBLISHED && is_null($article->race)) {
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
	
	/**
	 * Vrátí počet článků podle autora
	 * 
	 * @param int $userId
	 * @return int
	 */
	public function countAllAuthor($userId) {
		$rows = $this->database->table('article')
				->where('season', $this->season)
				->where('author', $userId);
		return $rows->count();
	}
	
	/**
	 * Vrátí počet článků podle závodu
	 * 
	 * @param int $raceId
	 * @return int
	 */
	public function countAllRace($raceId) {
		$rows = $this->database->table('article')
				->where('race', $raceId)
				->where('status', Article::PUBLISHED);
		return $rows->count();
	}
	
	/**
	 * 
	 * @param int $id ID řádku
	 * @return int počet smazaných řádků, FALSE pokud selže
	 */
	public function delete($id) {
		$this->database->table('comment')
				->where('article',$id)
				->delete();
		return $this->database->table('article')
				->get($id)->
				delete();
	}
	
	/**
	 * Vrátí pole článků podle autora
	 * 
	 * @param ArticleRepository $repository
	 * @param \Nette\Utils\Paginator $paginator
	 * @param int $userId
	 * @return \Articles[]
	 */
	public function getArticlesByAuthor(ArticleRepository $repository, $paginator, $userId) {
		$rows = $this->database->table('article')
				->where('author', $userId)
				->where('season', $this->season)
				->order('changed DESC')
				->limit($paginator->getLength(), $paginator->getOffset());
		$articles = array();
		foreach ($rows as $row) {
			$article = $this->getArticle($row->id);
			$article->repository = $repository;
			$articles[] = $article; 
		}
		return $articles;
	}
	
	/**
	 * Vrátí pole článků podle závodu
	 * 
	 * @param ArticleRepository $repository
	 * @param \Nette\Utils\Paginator $paginator
	 * @param int $raceId
	 * @return \Articles[]
	 */
	public function getArticlesByRace(ArticleRepository $repository, $paginator, $raceId) {
		$rows = $this->database->table('article')
				->where('race', $raceId)
				->order('changed DESC')
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
