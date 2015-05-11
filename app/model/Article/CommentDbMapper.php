<?php

/**
 * Description of CommentDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class CommentDbMapper extends BaseDbMapper {
	
	/**
	 * 
	 * @param int $id
	 * @return \Comment
	 * @throws Nette\InvalidArgumentException
	 */
	public function getComment($id) {
		$row = $this->database->table('comment')->get($id);
		if(!$row) {
			throw new Race\DbNotStoredException("Komentář $id neexistuje");
		}
		$comment = new Comment($row->id);				
		
		$comment->article = $row->article;
		$comment->title = $row->title;
		$comment->text = $row->text;
		$comment->author = $this->userRepository->getUser($row->author);
		$comment->posted = $row->posted;
		$comment->modified = $row->modified;
		
		return $comment;
	}
	
	/**
	 * 
	 * @param Nette\Utils\Paginator $paginator
	 * @param CommentRepository $repository
	 * @param int $articleId
	 * @return array of Comment
	 */
	public function getComments(Nette\Utils\Paginator $paginator, CommentRepository $repository, $articleId) {
		$rows = $this->database->table('comment')
				->order('posted DESC');
		if(!is_null($articleId)) {
			$rows = $rows->where('article', $articleId);
		}
		$page = $rows->limit($paginator->getLength(), $paginator->getOffset());
		$comments = array();
		foreach ($page as $row) {
			$comment = $this->getComment($row->id);
			$comment->repository = $repository;
			$comments[] = $comment;
		}
		return $comments;
	}
	
	/**
	 * 
	 * @param int $articleId
	 * @return int
	 */
	public function countAll($articleId) {
		$rows = $this->database->table('comment');				
		if(!is_null($articleId)) {
			$rows = $rows->where('article', $articleId);
		}
		return $rows->count();
	}
	
	/**
	 * 
	 * @param int $id ID komenráře
	 * @return int
	 */
	public function delete($id) {
		return $this->database->table('comment')
				->get($id)->
				delete();
	}
	
}
