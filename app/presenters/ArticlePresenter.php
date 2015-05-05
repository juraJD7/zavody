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
	protected $params = array();
	private $articles;
	private $category;	
	
	/**
	 * Article form factory
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentArticleForm()
	{
		$this->articleFactory->setAdminOnly($this->adminOnly);		
		$this->articleFactory->setRace($this->raceId);
		$form = $this->articleFactory->create();
		$articleId = $this->getParameter('articleId');					
		$this->articleFactory->setId($articleId);
		$form->onSuccess[] = function () {
			$this->flashMessage("Článek byl uložen.");
			$this->redirect("Article:detail", $this->articleFactory->id);
		};
		return $form;
	}
	
	protected function createComponentCommentForm()
	{
		if($this->skautIS->getUser()->isLoggedIn()) {
			$article = $this->getParameter('articleId');
			$this->commentFactory->setArticle($article);
			$form = $this->commentFactory->create();
			$form->onSuccess[] = function ($form) {	
				$article = $this->getParameter('articleId');
				$link = $this->link("Article:detail", $article);
				$form->getPresenter()->redirectUrl($link);
			};
			return $form;
		} else {
			throw new \Skautis\Wsdl\AuthenticationException("Pro tuto funkci je nutné se přihlásit");
		}
	}
	
	protected function createComponentEditComment()
	{
		if($this->skautIS->getUser()->isLoggedIn()) {
			$this->commentFactory->setId($this->getParameter('commentId'));		
			$form = $this->commentFactory->create();				
			$this->commentFactory->setArticle($this->getParameter('articleId'));			
			$form->onSuccess[] = function ($form) {					
				$article = $this->getParameter('articleId');
				$link = $this->link("Article:detail", $article);
				$form->getPresenter()->redirectUrl($link);
			};
			return $form;
		} else {
			throw new \Skautis\Wsdl\AuthenticationException("Pro tuto funkci je nutné se přihlásit");
		}
	}
	
	public function renderCreate($id = NULL, $adminOnly = 0) {
		$this->adminOnly = $adminOnly;
		$this->raceId = $id;		
		if (!$this->user->isInRole('admin') && !($this->user->isInRole('raceManager') && in_array($id, $this->user->races))) {
			throw new Nette\Security\AuthenticationException("Nemáte oprávnění k této operaci");
		}
	}

	public function renderDefault() {		
		$this->template->categories = $this->articleRepository->getAllCategories('article');
		$page = $this->getParameter('page');
		$this->category = $this->getParameter('category');
		if ($this->paginator->itemCount === NULL) {		
			$this->paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
			$this->paginator->setItemCount($this->articleRepository->countAll(\BaseDbMapper::COMMON, $this->category));
			$this->paginator->setItemsPerPage(1); 
			$this->paginator->setPage($page);			
		}
		$this->params['category'] = $this->category;
		$this->template->paginator = $this->paginator;
		$this->template->actionPaginator = "default";
		$this->template->params = $this->params;		
		
		$this->template->articles = $this->articleRepository->getArticles($this->paginator, \BaseDbMapper::COMMON, $this->category);
	}
	
	public function renderAdmin() {		
		if ($this->user->isInRole('admin') || $this->user->isInRole('raceManager')) {
			$this->template->categories = $this->articleRepository->getAllCategories('article');
			$page = $this->getParameter('page');
			$this->category = $this->getParameter('category');
			if ($this->paginator->itemCount === NULL) {		
				$this->paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
				$this->paginator->setItemCount($this->articleRepository->countAll(\BaseDbMapper::ADMIN_ONLY, $this->category));
				$this->paginator->setItemsPerPage(1); 
				$this->paginator->setPage($page);			
			}
			$this->params['category'] = $this->category;
			$this->template->paginator = $this->paginator;
			$this->template->actionPaginator = "admin";
			$this->template->params = $this->params;		

			$this->template->articles = $this->articleRepository->getArticles($this->paginator, \BaseDbMapper::ADMIN_ONLY, $this->category);			
			
		} else {
			throw new Nette\Security\AuthenticationException("Nemáte potřebná oprávnění");
		}
	}
	
	public function renderMy() {
		if ($this->user->isLoggedIn()) {			
			$page = $this->getParameter('page');
			$paginator = new Nette\Utils\Paginator;
			$paginator->setItemCount($this->articleRepository->countAllAuthor($this->user->id));
			$paginator->setItemsPerPage(5); 
			$paginator->setPage($page);
			$this->template->paginator = $paginator;
			$this->template->actionPaginator = "my";
			$this->template->params = array();

			$this->template->articles = $this->articleRepository->getArticlesByAuthor($paginator, $this->user->id);
		} else {
			throw new Nette\Security\AuthenticationException("Nemáte oprávnění k této operaci");
		}
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
	
	public function handleDelete($articleId) {
		try {
			$article = $this->articleRepository->getArticle($articleId);
		} catch (Nette\InvalidArgumentException $ex) {
			$this->error("Článek neexistuje");
		}
		if ($article->author->id == $this->user->id || $this->user->isInRole('admin')) {
			$result = $this->articleRepository->delete($articleId);
			if(!$result) {
				$this->error("Článek se nepodařilo smazat!");
			}
			$link = $this->link("Article:");
			$this->redirectUrl($link);
		}
	}
	
	public function handleDeleteComment($article, $commentId, $page) {
		try {
			$comment = $this->commentRepository->getComment($commentId);
		} catch (Nette\InvalidArgumentException $ex) {
			$this->error("Komentář neexistuje");
		}
		if ($comment->author->id == $this->user->id || $this->user->isInRole('admin')) {
			$result = $this->commentRepository->delete($commentId);
			if(!$result) {
				$this->error("Komentář se nepodařilo smazat!");
			}
			$link = $this->link("Article:detail", $article);
			$this->redirectUrl($link);
		}
	}
	
	public function handleEditComment($commentId, $page) {
		try {
			$comment = $this->commentRepository->getComment($commentId);
		} catch (Nette\InvalidArgumentException $ex) {
			$this->error("Komentář neexistuje");
		}
		$this->commentId = $commentId;
		if ($comment->author->id == $this->user->id) {
			$this->redrawControl("comment-$comment->id");
		}		
		$this->template->edit=$commentId;		
		$this['editComment']['title']->setDefaultValue($comment->title);
		$this['editComment']['text']->setDefaultValue($comment->text);
	}
	
	public function actionDetail($articleId, $commentId) {		
		try {
			$article = $this->articleRepository->getArticle($articleId);
			if (($article->status != \Article::PUBLISHED) &&
				!($this->user->isInRole('admin') || ($this->user->id == $article->author->id))) {
				throw new Nette\Security\AuthenticationException("Nemáte požadovaná oprávnění!");
			}				
			$page = $this->getParameter('page');
			$paginator = new Nette\Utils\Paginator;
			$paginator->setItemCount($this->commentRepository->countAll($articleId));
			$paginator->setItemsPerPage(3); 
			$paginator->setPage($page);
			$this->template->paginator = $paginator;
			$this->template->actionPaginator = "detail#comments";
			$this->template->params = array($articleId);
			$this->template->page = $page;
			
			$this->template->article = $article;	
			$this->template->comments = $this->commentRepository->getComments($paginator, $articleId);			
		} catch (\InvalidArgumentException $ex) {
			$this->error($ex);
		} catch (Nette\Security\AuthenticationException $ex) {
			$this->error($ex);
			$this->redirect("Article:default");
		}
	}
	
	public function actionEdit($articleId) {
		try {
			$article = $this->articleRepository->getArticle($articleId);
			if (!($this->user->isInRole('admin') || ($this->user->id == $article->author->id))) {
				throw new Nette\Security\AuthenticationException("Nemáte požadovaná oprávnění!");
			}
			
			$publish = (!is_null($article->published)) ? TRUE : FALSE;			
			$this['articleForm']['title']->setDefaultValue($article->title);
			$this['articleForm']['lead']->setDefaultValue($article->lead);
			$this['articleForm']['text']->setDefaultValue($article->text);
			$this['articleForm']['publish']->setDefaultValue($publish);			
			
		} catch (\InvalidArgumentException $ex) {
			$this->error($ex);
		} catch (Nette\Security\AuthenticationException $ex) {
			$this->error($ex);
			$this->redirect("Article:default");
		}
	}
}
