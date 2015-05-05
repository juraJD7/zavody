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
	 * @var \RaceRepository
	 * @inject
	 */
	public $raceRepository;
	
	/**
	 *
	 * @var \Nette\Utils\Paginator
	 * @inject
	 */
	public $paginator;
	
	private $adminOnly;
	private $raceId;
	private $actionPaginator;
	protected $params = array();
	private $questions;
	private $category;
	
	public function createComponentQuestionForm() {
		$this->questionFormFactory->setAdminOnly($this->adminOnly);
		$this->questionFormFactory->setRace($this->raceId);
		$form = $this->questionFormFactory->create();		
		$form->onSuccess[] = function ($form) {
			$this->flashMessage("Otázka byla položena.");
			$this->redirect('this');
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
					$this->redirect('this');
				};
				return $form;
			});
		} else {
			throw new \Skautis\Wsdl\AuthenticationException("Pro tuto funkci je nutné se přihlásit");
		}
	}
	
	public function renderDefault() {
		$this->adminOnly = 0;
		$page = $this->getParameter('page');
		
		if (is_null($this->category)) {
			$this->category = $this->getParameter('category');
		}
		
		if ($this->paginator->itemCount === NULL) {		
			$this->paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
			$this->paginator->setItemCount($this->questionRepository->countAll(\BaseDbMapper::COMMON, $this->category));
			$this->paginator->setItemsPerPage(1); 
			$this->paginator->setPage($page);			
		}
		
		if (is_null($this->actionPaginator)) {
			$this->actionPaginator = "default";
		}		
				
		if (is_null($this->questions)) {			
			$this->questions = $this->questionRepository->getQuestions($this->paginator, \BaseDbMapper::COMMON, $this->category);
		}		
		$this->params['category'] = $this->category;		
		
		$this->template->paginator = $this->paginator;
		$this->template->actionPaginator = $this->actionPaginator;
		$this->template->params = $this->params;		
		$this->template->questions = $this->questions;
		
		$this->template->categories = $this->questionRepository->getAllCategories('question');		
	}
	
	public function renderAdmin() {
		$this->adminOnly = 1;
		if ($this->user->isInRole('admin') || $this->user->isInRole('raceManager')) {
			$page = $this->getParameter('page');

			if (is_null($this->category)) {
				$this->category = $this->getParameter('category');
			}

			if ($this->paginator->itemCount === NULL) {		
				$this->paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
				$this->paginator->setItemCount($this->questionRepository->countAll(\BaseDbMapper::ADMIN_ONLY, $this->category));
				$this->paginator->setItemsPerPage(1); 
				$this->paginator->setPage($page);			
			}

			if (is_null($this->actionPaginator)) {
				$this->actionPaginator = "admin";
			}		

			if (is_null($this->questions)) {			
				$this->questions = $this->questionRepository->getQuestions($this->paginator, \BaseDbMapper::ADMIN_ONLY, $this->category);
			}		
			$this->params['category'] = $this->category;		

			$this->template->paginator = $this->paginator;
			$this->template->actionPaginator = $this->actionPaginator;
			$this->template->params = $this->params;		
			$this->template->questions = $this->questions;

			$this->template->categories = $this->questionRepository->getAllCategories('question');	
			
			$this->setView("default");
		} else {
			throw new Nette\Security\AuthenticationException("Nemáte potřebná oprávnění");
		}
	}
	
	public function renderMy() {	
		$this->adminOnly = 0;
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
	
	public function renderRace($id) {	
		$this->adminOnly = 0;
		$this->raceId = $id;
		$page = $this->getParameter('page');	

		$paginator = new Nette\Utils\Paginator(); //bez tohoto řádku to hází error na produkci. Proč?
		$paginator->setItemCount($this->questionRepository->countAllRace($this->raceId));
		$paginator->setItemsPerPage(1); 
		$paginator->setPage($page);	

		$this->template->race = $this->raceRepository->getRace($id);
		$this->template->paginator = $paginator;
		$this->template->actionPaginator = "race";
		$this->template->params = array('id' => $id);		
		$this->template->questions = $this->questionRepository->getQuestionsByRace($paginator, $this->raceId);		
	}
}
