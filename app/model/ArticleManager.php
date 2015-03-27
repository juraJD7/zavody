<?php

/**
 * Description of ArticleManager
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class ArticleManager extends BaseManager {
	
	
	public function load($id) {		
		$row = $this->database->table('article')->get((int)$id);
		if(!$row) {
			throw new Nette\InvalidArgumentException("Článek $id neexistuje");
		}
		return new Article($this, $row);
	}
	
	public function loadAllPublished($paginator) {
		$rows = $this->database->table('article')
				->where('status',  Article::PUBLISHED)
				->order('modified DESC')
				->limit($paginator->getLength(), $paginator->getOffset());
		$articles = array();
		foreach ($rows as $row) {
			$articles[] = new Article($this, $row);
		}
		return $articles;
	}
	
	public function countAll() {
		$rows = $this->database->table('article')
				->where('published NOT', NULL);
		return $rows->count();
	}
	
	public function delete($id) {
		$this->database->table('comment')
				->where('article',$id)
				->delete();
		return $this->database->table('article')
				->get($id)->
				delete();
	}

	public function getAuthorName($id) {
		$userManager = new UserManager($this->skautIS, $this->database);
		$user = $userManager->load($id);
		return $user->displayName;
	}
	
	public function getNumComments($articleId) {
		$commentManager = new CommentManager($this->skautIS, $this->database);
		return $commentManager->countAll($articleId);
	}
	
}
