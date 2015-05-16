<?php

namespace App\Forms;

use Nette\Forms\Controls,
	Nette\Application\UI\Form;
	

/**
 * ArticleFormFactory
 * 
 * Továrna na formuláře pro články
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class ArticleFormFactory extends BaseFormFactory {
		
	private $id;
	private $articleRepository;
	private $adminOnly;
	private $race;
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param \Nette\Database\Context $database
	 * @param \ArticleRepository $articleRepository
	 */
	public function __construct(\Skautis\Skautis $skautIS, \Nette\Database\Context $database, \ArticleRepository $articleRepository) {
		parent::__construct($skautIS, $database);
		$this->articleRepository = $articleRepository;
		$this->race = NULL;
		$this->adminOnly = 0;
	}
	
	public function setId($id) {
		$this->id = (int) $id;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setAdminOnly($adminOnly) {
		$this->adminOnly = $adminOnly;
	}
	
	public function setRace($race) {
		$this->race = $race;
	}

	/**
	 * @return Form
	 */
	public function create()
	{	
		// vytvoření seznamu kategorií pomocí vlastní komponenty
		$categories = $this->articleRepository->getAllCategories('article');
		$items = array();
		foreach ($categories as $category) {
			$items[$category->id] = $category->name;
		}		
		$checkboxList = new Controls\MyCheckboxList();
		$checkboxList->setItems($items);
		
		// samotný formulář
		$form = new Form;
		
		$form->addComponent($checkboxList, 'categories');
		
		$form->addText('title', 'Nadpis:')
			->setRequired('Je nutné vyplnit nadpis článku.');
		$form->addTextArea('lead', 'Krátký popis:', 30, 5)
			->setRequired('Je nutné vyplnit krátký popis článku.');
		$form->addTextArea('text', 'Text článku', 60, 10)
				->setAttribute('class','mceEditor');

		$form->addCheckbox('publish', ' Ihned publikovat');
		$form->addHidden('admin_only', $this->adminOnly);
		$form->addHidden('race', $this->race);
		$form->addSubmit('send', 'Uložit');

		$form->onSuccess[] = array($this, 'formSucceeded');
		
		$renderer = $form->getRenderer();
		$this->addBootstrapRendering($renderer, $form);
		
		return $form;
	}

	public function formSucceeded($form, $values)
	{	
		$values->race = $values->race ?: NULL;		
		$status = $values->publish ? 1 : 0;
		$dateTime = date("Y-m-d H:i:s");
		$user = $this->skautIS->usr->UserDetail()->ID;
		
		// validace HTML dat
		$config = \HTMLPurifier_Config::createDefault();
		$purifier = new \HTMLPurifier($config);
		$cleanText = $purifier->purify($values->text);
		
		$data = array(
			'status' => $status,
			'author' => $user,
			'title' => $values->title,
			'lead' => $values->lead,
			'text' => $cleanText,
			'modified' => $dateTime,
			'changed' => $dateTime,
			'admin_only' => $values->admin_only,
			'race' => $values->race,
			'season' => $this->season
		);
		
		//uložení nebo aktualizace
		if($this->id) {
			$article = $this->database->table('article')->get($this->id);
			if ($article->status == 0 && $status == 1) {
				$data['published'] = $dateTime;
			}
			$article->update($data);
		} else {
			$data['published'] = $dateTime;
			$row = $this->database->table('article')->insert($data);
			$this->id = $row->id;			
		}
		$this->updateCategories($values->categories, $this->id);
		
	}
	
	/**
	 * Aktualizuje vazby kategorie na článek
	 * 
	 * @param array $categories
	 * @param int $articleId
	 */
	private function updateCategories($categories, $articleId) {
		// pokud se článek edituje, smažou se staré kategorie
		if (!is_null($this->id)) {
			$this->database->table('article_category')
				->where('article_id',  $this->id)
				->delete();
		}
		// uložení nově zvolených kategorií článku
		foreach ($categories as $category) {
			$this->database->table('article_category')
				->insert(array(
					'category_id' => $category,
					'article_id' => $articleId
				));
		}
	}

}

