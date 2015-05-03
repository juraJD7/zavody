<?php

/**
 * Description of ArticleRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class ArticleRepository {
	
	private $dbMapper;	
	private $commentRepository;


	public function __construct(ArticleDbMapper $dbMapper, CommentRepository $commentRepository) {
		$this->dbMapper = $dbMapper;
		$this->commentRepository = $commentRepository;
	}

	public function getArticle($id) {		
		$article = $this->dbMapper->getArticle($id);
		$article->repository = $this;
		return $article;
	}
	
	public function getArticles($paginator, $status = NULL, $category = NULL) {
		return $this->dbMapper->getArticles($paginator, $this, $status, $category);
	}
	
	public function countAll($status = NULL, $category = NULL) {
		return $this->dbMapper->countAll($status, $category);
	}
	
	public function countAllAuthor($userId) {
		return $this->dbMapper->countAllAuthor($userId);
	}
	
	public function delete($id) {
		return $this->dbMapper->delete($id);
	}
	
	public function getNumComments($articleId) {
		return $this->commentRepository->countAll($articleId);
	}
	
	public function getArticlesByAuthor($paginator, $userId) {
		return $this->dbMapper->getArticlesByAuthor($this, $paginator, $userId);
	}
}
