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
		
		return $article;
	}
	
	/**
	 * 
	 * @param Nette\Utils\Paginator $paginator
	 * @param ArticleRepository $repository
	 * @param int $status
	 * @return type
	 */
	public function getArticles(Nette\Utils\Paginator $paginator, ArticleRepository $repository, $status, $category) {
		$rows = $this->database->table('article')				
				->order('modified DESC');
		if (is_null($status)) {
			$rows = $rows->where('status',  $status);
		}		
		//to DO CATEGORY
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
	 * 
	 * @param int $status
	 * @param int $category
	 * @return int
	 */
	public function countAll($status, $category) {
		$rows = $this->database->table('article');
		if (is_null($status)) {
			$rows = $rows->where('status',  $status);
		}		
		//to DO CATEGORY
				
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
}
