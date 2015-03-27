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
	
	private $commentId;
	
	/**
	 * Article form factory
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentArticleForm()
	{
		$form = $this->articleFactory->create();
		$articleId = $this->getParameter('articleId');					
		$this->articleFactory->setId($articleId);
		$form->onSuccess[] = function ($form) {
			$this->flashMessage("Článek byl uložen.");			
			$link = $this->link("Article:detail", $this->articleFactory->id);
			$form->getPresenter()->redirectUrl($link);
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
			$this->commentFactory->setId($this->commentId);		
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
	
	public function renderCreate() {
		if (!$this->user->isInRole('admin') && !$this->user->isInRole('raceManager')) {
			throw new Nette\Security\AuthenticationException("Nemáte oprávnění k této operaci");
		}
	}

	public function renderDefault() {
		
		$page = $this->getParameter('page');
		$paginator = new Nette\Utils\Paginator;
		$paginator->setItemCount($this->articleManager->countAll());
		$paginator->setItemsPerPage(2); 
		$paginator->setPage($page);
		$this->template->paginator = $paginator;
		$this->template->actionPaginator = "default";
		$this->template->params = array();
		
		$this->template->articles = $this->articleManager->loadAllPublished($paginator);
	}
	
	public function handleDelete($articleId) {
		try {
			$article = $this->articleManager->load($articleId);
		} catch (Nette\InvalidArgumentException $ex) {
			$this->error("Článek neexistuje");
		}
		if ($article->author == $this->user->id || $this->user->isInRole('admin')) {
			$result = $this->articleManager->delete($articleId);
			if(!$result) {
				$this->error("Článek se nepodařilo smazat!");
			}
			$link = $this->link("Article:");
			$this->redirectUrl($link);
		}
	}
	
	public function handleDeleteComment($article, $commentId) {
		try {
			$comment = $this->commentManager->load($commentId);
		} catch (Nette\InvalidArgumentException $ex) {
			$this->error("Komentář neexistuje");
		}
		if ($comment->author == $this->user->id || $this->user->isInRole('admin')) {
			$result = $this->commentManager->delete($commentId);
			if(!$result) {
				$this->error("Komentář se nepodařilo smazat!");
			}
			$link = $this->link("Article:detail", $article);
			$this->redirectUrl($link);
		}
	}
	
	public function handleEditComment($commentId) {
		try {
			$comment = $this->commentManager->load($commentId);
		} catch (Nette\InvalidArgumentException $ex) {
			$this->error("Komentář neexistuje");
		}
		$this->commentId = $commentId;
		if ($comment->author == $this->user->id) {
			$this->redrawControl("comment-$comment->id");
		}
		$this->template->edit=$commentId;		
		$this['editComment']['title']->setDefaultValue($comment->title);
		$this['editComment']['text']->setDefaultValue($comment->text);
	}
	
	public function actionDetail($articleId) {		
		try {
			$article = $this->articleManager->load($articleId);
			if (($article->status != \Article::PUBLISHED) &&
				!($this->user->isInRole('admin') || ($this->user->id == $article->author))) {
				throw new Nette\Security\AuthenticationException("Nemáte požadovaná oprávnění!");
			}				
			
			$page = $this->getParameter('page');
			$paginator = new Nette\Utils\Paginator;
			$paginator->setItemCount($this->commentManager->countAll($articleId));
			$paginator->setItemsPerPage(3); 
			$paginator->setPage($page);
			$this->template->paginator = $paginator;
			$this->template->actionPaginator = "detail#comments";
			$this->template->params = array($articleId);
			
			$this->template->article = $article;	
			$this->template->comments = $this->commentManager->loadAll($paginator, $articleId);			
		} catch (\InvalidArgumentException $ex) {
			$this->error($ex);
		} catch (Nette\Security\AuthenticationException $ex) {
			$this->error($ex);
			$this->redirect("Article:default");
		}
	}
	
	public function actionEdit($articleId) {
		try {
			$article = $this->articleManager->load($articleId);
			if (!($this->user->isInRole('admin') || ($this->user->id == $article->author))) {
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
