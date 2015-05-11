<?php

/**
 * Description of CommentRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class CommentRepository {
	
	private $dbMapper;
	
	public function __construct(CommentDbMapper $dbMapper) {
		$this->dbMapper = $dbMapper;
	}

	public function getComment($id) {
		$comment = $this->dbMapper->getComment($id);
		$comment->repository = $this;
		return $comment;
	}
	
	public function getComments($paginator, $articleId = NULL) {
		return $this->dbMapper->getComments($paginator, $this, $articleId);
	}
	
	public function countAll($article = NULL) {
		return $this->dbMapper->countAll($article);
	}
	
	public function delete($id) {
		return $this->dbMapper->delete($id);
	}	
}
