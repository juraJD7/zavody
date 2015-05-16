<?php

namespace App\Presenters;

use Nette,
	\App\Forms\ArticleFormFactory,
	\App\Forms\CommentFormFactory;


/**
 * Description of ArticlePresenter
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class ArticlePresenter extends BasePresenter {
	
	/** 
	 * @var ArticleFormFactory
	 * @inject 
	 */
	public $articleFactory;
	
	/** 
	 * @var CommentFormFactory
	 * @inject 
	 */
	public $commentFactory;
	
	/**
	 *
	 * @var \ArticleRepository
	 * @inject
	 */
	public $articleRepository;
	
	/**
	 *
	 * @var \RaceRepository
	 * @inject
	 */
	public $raceRepository;
	
	/**
	 *
	 * @var \CommentRepository
	 * @inject
	 */
	public $commentRepository;

	/**
	 *
	 * @var \Nette\Utils\Paginator
	 * @inject
	 */
	public $paginator;

	private $commentId;
	private $raceId;
	private $page;
	private $adminOnly;
	private $actionPaginator;
	
	/**
	 * Parametry URL při stránkování
	 * 
	 * @var array 
	 */
	protected $params = array();
	private $articles;
	private $category;	
	
	/**
	 * Formulář článku
	 * 
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentArticleForm()
	{
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}		
		if (!$this->user->isInRole('admin') && !$this->user->isInRole('raceManager')) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}	
		// v přápadě editace článku zjištění ID z url
		$articleId = $this->getParameter('articleId');
		$this->articleFactory->setAdminOnly($this->adminOnly);		
		$this->articleFactory->setRace($this->raceId);
		$form = $this->articleFactory->create();
							
		$this->articleFactory->setId($articleId);
		$form->onSuccess[] = function () {
			$this->flashMessage("Článek byl uložen.");
			$this->redirect("Article:detail", $this->articleFactory->id);
		};
		return $form;
	}
	
	/**
	 * Formulář pro nový komentář
	 * 
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentCommentForm()
	{
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		//zjištšní ID článku z URL
		$article = $this->getParameter('articleId');
		$this->commentFactory->setArticle($article);
		$form = $this->commentFactory->create();
		$form->onSuccess[] = function () {	
			$article = $this->getParameter('articleId');
			$this->redirect("Article:detail", $article);
		};
		return $form;		
	}
	
	/**
	 * Formulář pro editaci formuláře 
	 * 
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentEditComment()
	{
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}		
		//zjištění ID komentáře z URL
		$commentId = $this->getParameter('commentId');		
		$comment = $this->commentRepository->getComment($commentId);
		
		if ($comment->author->id != $this->user->id) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}		
		$this->commentFactory->setId($commentId);		
		$form = $this->commentFactory->create();				
		$this->commentFactory->setArticle($this->getParameter('articleId'));			
		$form->onSuccess[] = function () {					
			$article = $this->getParameter('articleId');
			$this->redirect("Article:detail", $article);
		};
		return $form;		
	}
	
	public function renderCreate($id = NULL, $adminOnly = 0) {
		$this->adminOnly = $adminOnly;
		$this->raceId = $id;
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		if (!$this->user->isInRole('admin') && !($this->user->isInRole('raceManager') && in_array($id, $this->user->identity->data["races"]))) {
			throw new Nette\Security\AuthenticationException("Nemáte oprávnění k této operaci");
		}
	}

	public function renderDefault() {		
		$this->template->categories = $this->articleRepository->getAllCategories('article');
		$page = $this->getParameter('page');
		$this->category = $this->getParameter('category');
		
		//pokud ještě není nastaveno, nastaví parametry pro stránkování
		if ($this->paginator->itemCount === NULL) {		
			$this->paginator = new Nette\Utils\Paginator();
			$this->paginator->setItemCount($this->articleRepository->countAll(\BaseDbMapper::COMMON, $this->category));
			$this->paginator->setItemsPerPage(5); 
			$this->paginator->setPage($page);			
		}
		$this->params['category'] = $this->category;
		$this->template->paginator = $this->paginator;
		$this->template->actionPaginator = "default";
		$this->template->params = $this->params;		
		
		$this->template->articles = $this->articleRepository->getArticles($this->paginator, \BaseDbMapper::COMMON, $this->category);
	}
	
	public function renderAdmin() {		
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}		
		if (!$this->user->isInRole('admin') && !$this->user->isInRole('raceManager')) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}	
		$this->template->categories = $this->articleRepository->getAllCategories('article');
		$page = $this->getParameter('page');
		$this->category = $this->getParameter('category');
		
		//pokud ještě není nastaveno, nastaví parametry pro stránkování
		if ($this->paginator->itemCount === NULL) {		
			$this->paginator = new Nette\Utils\Paginator();
			$this->paginator->setItemCount($this->articleRepository->countAll(\BaseDbMapper::ADMIN_ONLY, $this->category));
			$this->paginator->setItemsPerPage(5); 
			$this->paginator->setPage($page);			
		}
		$this->params['category'] = $this->category;
		$this->template->paginator = $this->paginator;
		$this->template->actionPaginator = "admin";
		$this->template->params = $this->params;		

		$this->template->articles = $this->articleRepository->getArticles($this->paginator, \BaseDbMapper::ADMIN_ONLY, $this->category);		
	}
	
	public function renderMy() {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}		
		$page = $this->getParameter('page');
		$paginator = new Nette\Utils\Paginator;
		$paginator->setItemCount($this->articleRepository->countAllAuthor($this->user->id));
		$paginator->setItemsPerPage(5); 
		$paginator->setPage($page);
		$this->template->paginator = $paginator;
		$this->template->actionPaginator = "my";
		$this->template->params = array();

		$this->template->articles = $this->articleRepository->getArticlesByAuthor($paginator, $this->user->id);		
	}
	
	public function renderRace($id) {
		$this->raceId = $id;
		$page = $this->getParameter('page');
		$paginator = new Nette\Utils\Paginator;
		$paginator->setItemCount($this->articleRepository->countAllRace($id));		
		$paginator->setItemsPerPage(5); 
		$paginator->setPage($page);
		$this->template->paginator = $paginator;
		$this->template->actionPaginator = "race";
		$this->template->params = array('id' => $id);
		$this->template->race = $this->raceRepository->getRace($id);
		$this->template->articles = $this->articleRepository->getArticlesByRace($paginator, $id);		
	}
	
	/**
	 * Signál pro smazání článku
	 * 
	 * @param int $articleId
	 * @throws Nette\Security\AuthenticationException
	 * @throws \Race\PermissionException
	 */
	public function handleDelete($articleId) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}	
		//mazat článek může autor, admin nebo editor závodu pro svůj závodu
		$article = $this->articleRepository->getArticle($articleId);
		if ($article->author->id != $this->user->id 
				&& !$this->user->isInRole('admin') 
				&& !($this->user->isInRole('raceManager') && in_array($article->race, $this->user->identity->data["races"]))) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}
		$result = $this->articleRepository->delete($articleId);
		if(!$result) {
			$this->error("Článek se nepodařilo smazat!");
		}
		$this->flashMessage("Článek byl smazán.");
		$this->redirect("Article:");		
	}
	
	/**
	 * Signál pro smazání komentáře
	 * 
	 * @param int $article
	 * @param int $commentId
	 * @param int $page
	 * @throws Nette\Security\AuthenticationException
	 * @throws \Race\PermissionException
	 */
	public function handleDeleteComment($article, $commentId, $page) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		//mazat komentáře může autor, admin, nebo editor závodu pro svůj závod
		$comment = $this->commentRepository->getComment($commentId);	
		$art = $this->articleRepository->getArticle($article);
		if ($comment->author->id != $this->user->id
				&& !$this->user->isInRole('admin') 
				&& !($this->user->isInRole('raceManager') && in_array($art->race, $this->user->identity->data["races"]))) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}
		$result = $this->commentRepository->delete($commentId);
		if(!$result) {
			$this->error("Komentář se nepodařilo smazat!");
		}
		$this->flashMessage("Komenrář byl smazán.");
		$this->redirect("Article:detail", $article);
	}
	
	public function handleEditComment($commentId, $page) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$comment = $this->commentRepository->getComment($commentId);
		if ($comment->author->id != $this->user->id) {
			throw new \Race\PermissionException("Nemáte oprávnění k této akci");
		}		
		$this->commentId = $commentId;		
		$this->redrawControl("comment-$comment->id");				
		$this->template->edit=$commentId;		
		$this['editComment']['title']->setDefaultValue($comment->title);
		$this['editComment']['text']->setDefaultValue($comment->text);
	}
	
	public function actionDetail($articleId, $commentId) {
		$article = $this->articleRepository->getArticle($articleId);
		if (($article->status != \Article::PUBLISHED) &&
				!($this->user->isInRole('admin') 
					|| ($this->user->isInRole('raceManager') && in_array($article->race, $this->user->identity->data["races"])))) {
				throw new \Race\PermissionException("Nemáte požadovaná oprávnění!");
		}	
		$page = $this->getParameter('page');
		$paginator = new Nette\Utils\Paginator;
		$paginator->setItemCount($this->commentRepository->countAll($articleId));
		$paginator->setItemsPerPage(5); 
		$paginator->setPage($page);
		$this->template->paginator = $paginator;
		$this->template->actionPaginator = "detail#comments";
		$this->template->params = array($articleId);
		$this->template->page = $page;

		$this->template->article = $article;	
		$this->template->comments = $this->commentRepository->getComments($paginator, $articleId);
	}
	
	public function actionEdit($articleId) {
		if (!$this->user->isLoggedIn()) {
			throw new Nette\Security\AuthenticationException("Pro tuto akci je nutné se přihlásit");
		}
		$article = $this->articleRepository->getArticle($articleId);		
		if (!$this->user->isInRole('admin') 
				&& !($this->user->isInRole('raceManager') && in_array($article->race, $this->user->identity->data["races"]))) {
			throw new \Race\PermissionException("Nemáte požadovaná oprávnění!");
		}
		//nastavení výchozích hodnot pro editaci článku
		$publish = (!is_null($article->published)) ? TRUE : FALSE;			
		$this['articleForm']['title']->setDefaultValue($article->title);
		$this['articleForm']['lead']->setDefaultValue($article->lead);
		$this['articleForm']['text']->setDefaultValue($article->text);
		$this['articleForm']['publish']->setDefaultValue($publish);	
		$this['articleForm']['admin_only']->setDefaultValue($article->adminOnly);
		$this['articleForm']['race']->setDefaultValue($article->race);
		$categories = $this->articleRepository->getCategoriesByArticle($article->id);			
		$items = array();
		foreach ($categories as $category) {
			$items[] = $category->id;
		}			
		$this['articleForm']['categories']->setDefaultValue($items);
	}
}
