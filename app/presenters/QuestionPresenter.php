<?php

namespace App\Presenters;

use Nette,
	Nette\Application\UI\Multiplier;


/**
 * Description of QuestionPresenter
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class QuestionPresenter extends BasePresenter {
	
	/**
	 *
	 * @var \QuestionRepository
	 * @inject
	 */
	public $questionRepository;
	
	/** 
	 * @var \App\Forms\QuestionFormFactory
	 * @inject 
	 */
	public $questionFormFactory;
	
	/** 
	 * @var \App\Forms\AnswerFormFactory
	 * @inject 
	 */
	public $answerFormFactory;
	
	/**
	 *
	 * @var \Nette\Utils\Paginator
	 * @inject
	 */
	public $paginator;
	
	private $actionPaginator;
	protected $params = array();
	private $questions;
	private $category;
	
	public function createComponentQuestionForm() {
		$form = $this->questionFormFactory->create();		
		$form->onSuccess[] = function ($form) {
			$this->flashMessage("Otázka byla položena.");			
			$link = $this->link("Question:");
			$form->getPresenter()->redirectUrl($link);
		};
		return $form;
	}
	
	protected function createComponentAnswerForm()
	{		
		if($this->skautIS->getUser()->isLoggedIn()) {
			return new Multiplier(function ($questionId) {				
				$this->answerFormFactory->setQuestion($questionId);
				$form = $this->answerFormFactory->create();
				$form->onSuccess[] = function ($form) {
					$link = $this->link("Question:");
					$form->getPresenter()->redirectUrl($link);
				};
				return $form;
			});
		} else {
			throw new \Skautis\Wsdl\AuthenticationException("Pro tuto funkci je nutné se přihlásit");
		}
	}
	
	public function renderDefault() {		
		$page = $this->getParameter('page');
		
		if (is_null($this->category)) {
			$this->category = $this->getParameter('category');
		}
		
		if ($this->paginator->itemCount === NULL) {		
			$this->paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
			$this->paginator->setItemCount($this->questionRepository->countAll($this->category));
			$this->paginator->setItemsPerPage(5); 
			$this->paginator->setPage($page);			
		}
		
		if (is_null($this->actionPaginator)) {
			$this->actionPaginator = "default";
		}		
				
		if (is_null($this->questions)) {			
			$this->questions = $this->questionRepository->getQuestions($this->paginator, $this->category);
		}		
		$this->params['category'] = $this->category;		
		
		$this->template->paginator = $this->paginator;
		$this->template->actionPaginator = $this->actionPaginator;
		$this->template->params = $this->params;		
		$this->template->questions = $this->questions;
		
		$this->template->categories = $this->questionRepository->getAllCategories();		
	}
	
	public function renderMy() {		
		if ($this->user->isLoggedIn()) {
			$page = $this->getParameter('page');	

			$paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
			$paginator->setItemCount($this->questionRepository->countAllAuthor($this->user->id));
			$paginator->setItemsPerPage(5); 
			$paginator->setPage($page);	

			$this->template->paginator = $paginator;
			$this->template->actionPaginator = "my";
			$this->template->params = array();		
			$this->template->questions = $this->questionRepository->getQuestionsByAuthor($paginator, $this->user->id);
		} else {
			throw new Nette\Security\AuthenticationException("Pro tuto funkci je potřeba se přihlásit.");
		}
	}
	
	public function handleChangeCategory() {
		if($this->isAjax()) {				
			$httpRequest = $this->context->getByType('Nette\Http\Request');
			$this->category = $httpRequest->getPost('category');
			$page = $this->getParameter('page');
			$this->paginator->setItemCount($this->questionRepository->countAll($this->category));			
			$this->paginator->setItemsPerPage(5); 
			$this->paginator->setPage($page);			
			$this->actionPaginator = "default";					
			$this->questions = $this->questionRepository->getQuestions($this->paginator, $this->category);			
			$this->redrawControl('category');
		}			
	}
	
}
