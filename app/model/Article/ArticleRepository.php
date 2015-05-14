<?php

/**
 * Description of ArticleRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class ArticleRepository {
	
	private $dbMapper;	
	private $commentRepository;
	
	const IMAGEPATH = "/img/articles/";


	public function __construct(ArticleDbMapper $dbMapper, CommentRepository $commentRepository) {
		$this->dbMapper = $dbMapper;
		$this->commentRepository = $commentRepository;
	}

	public function getArticle($id) {		
		$article = $this->dbMapper->getArticle($id);
		$article->repository = $this;
		return $article;
	}
	
	public function getArticles($paginator, $adminOnly = 0, $category = NULL) {
		return $this->dbMapper->getArticles($paginator, $this, $adminOnly, $category);
	}
	
	public function countAll($adminOnly = 0, $category = NULL) {
		return $this->dbMapper->countAll($adminOnly, $category);
	}
	
	public function countAllAuthor($userId) {
		return $this->dbMapper->countAllAuthor($userId);
	}
	
	public function countAllRace($raceId) {
		return $this->dbMapper->countAllRace($raceId);
	}
	
	public function getAllCategories($type) {
		return $this->dbMapper->getAllCategories($type);
	}
	
	public function getCategoriesByArticle($id) {
		return $this->dbMapper->getCatogoriesByArticle($id);
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
	
	public function getArticlesByRace($paginator, $raceId) {
		return $this->dbMapper->getArticlesByRace($this, $paginator, $raceId);
	}
	
	public function getImage($articleId) {
		$filename = self::IMAGEPATH . "$articleId" . ".jpg";
		if (file_exists($filename)) {
			return $filename;
		}
		return self::IMAGEPATH . "default.jpg";
	}
}
