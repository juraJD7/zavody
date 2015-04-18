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
		$this->dbMapper->countAll($status, $category);
	}
	
	public function delete($id) {
		return $this->dbMapper->delete($id);
	}
	
	public function getNumComments($articleId) {
		return $this->commentRepository->countAll($articleId);
	}
}
