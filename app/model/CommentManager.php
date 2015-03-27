<?php

/**
 * Description of CommentManager
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class CommentManager extends BaseManager {
	
	public function load($id) {
		$row = $this->database->table('comment')->get((int)$id);
		if(!$row) {
			throw new Nette\InvalidArgumentException("Komentář $id neexistuje");
		}
		return new Comment($this, $row);
	}

	public function loadAll($paginator, $article = NULL) {
		$rows = $this->database->table('comment')
				->order('posted DESC');
		if(!is_null($article)) {
			$rows = $rows->where('article', $article);
		}
		$page = $rows->limit($paginator->getLength(), $paginator->getOffset());
		$comments = array();
		foreach ($page as $row) {
			$comments[] = new Comment($this, $row);
		}
		return $comments;
	}
	
	public function countAll($article = NULL) {
		$rows = $this->database->table('comment');				
		if(!is_null($article)) {
			$rows = $rows->where('article', $article);
		}
		return $rows->count();
	}
	
	public function delete($id) {
		return $this->database->table('comment')
				->get($id)->
				delete();
	}
	
	public function getAuthorName($id) {
		$userManager = new UserManager($this->skautIS, $this->database);
		$user = $userManager->load($id);
		return $user->displayName;
	}
}
