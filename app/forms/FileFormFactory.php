<?php

namespace App\Forms;

use Nette\Forms\Controls,
	Nette\Application\UI\Form;	

/**
 * FileFormFactory
 * 
 * Továrna na formuláře pro nahrávání souborů
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class FileFormFactory extends BaseFormFactory {
	
	private $fileRepository;
	
	private $id;
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param \Nette\Database\Context $database
	 * @param \FileRepository $fileRepository
	 */
	public function __construct(\Skautis\Skautis $skautIS, \Nette\Database\Context $database, \FileRepository $fileRepository) {
		parent::__construct($skautIS, $database);
		$this->fileRepository = $fileRepository;
	}
	
	public function setId($id) {
		if (!is_int($id)) {
			throw new \Nette\MemberAccessException("Id formuláře musí být integer");
		}
		$this->id = $id;
	}

	/**
	 * @return Form
	 */
	public function create()
	{
		$categories = $this->fileRepository->getAllCategories('file');
		
		$items = array();
		foreach ($categories as $category) {
			$items[$category->id] = $category->name;
		}
		
		$form = new Form;	
		
		$checkboxList = new Controls\MyCheckboxList();
		$checkboxList->setItems($items);
		
		$form->addComponent($checkboxList, 'categories');
		
		$form->addText('name', 'Název:')
			->setRequired('Je nutné vyplnit název souboru.');		
		
		
		$form->addTextArea('description', 'Krátký popis:', 30, 5);		

		$form->addUpload('file', 'Soubor:');			

		$form->addSubmit('send', 'Uložit');

		$form->onSuccess[] = array($this, 'formSucceeded');
		
		$renderer = $form->getRenderer();
		$this->addBootstrapRendering($renderer, $form);
		
		return $form;
	}
	
	public function formSucceeded(Form $form, $values)
	{
		if($values->file->isOk()) {
			//povolené typy souborů
			$whiteList = $this->fileRepository->getWhiteList();
			$user = $this->skautIS->usr->UserDetail()->ID;	
			$type = $values->file->getContentType();
			// pokud je soubor povolen
			if(in_array($type, $whiteList)) {
				$competition = $this->database->table('season')
						->get($this->season)->competition;
				if (!is_null($this->id)) {
					// pokud editujeme, smažeme starou verzi souboru
					$file = $this->database->table('file')->get($this->id);
					unlink(\FileRepository::BASEDIR . $file->path);
				}
				$subPath = $this->getSubPath($values->file);								
				$path = \FileRepository::BASEDIR . "$subPath";				
				$values->file->move($path);
				$data = array(
					'path' => $subPath,
					'name' => $values->name,
					'description' => $values->description,
					'type' => $type,
					'size' => $values->file->getSize(),
					'author' => $user,
					'competition' => $competition
				);
				// vytvoření nebo aktualizace dat
				if (!is_null($this->id)) {
					$file->update($data);					
				} else {
					$file = $this->database->table('file')->insert($data);
				}				
				$this->updateCategories($values->categories, $file);
			} else {
				$form->addError("Nelze nahrát souboru typu $type");
			}
		} else {
			$error = $values->file->getError();
			// pokud editujeme nejedná se o chybu, pouze aktualizujeme databázi
			if (!is_null($this->id) && $error == UPLOAD_ERR_NO_FILE) {
				$file = $this->database->table('file')->get($this->id);
				$file->update(array(					
					'name' => $values->name,
					'description' => $values->description,
				));
				$this->updateCategories($values->categories, $file);
			// pokud je chyba jeného rázu, je to skutečně chyba
			} else {
				$form->addError("Při nahrávání se objevila chyba. Kod: $error");
			}
		}	
	}
	
	private function updateCategories($categories, $file) {
		if (!is_null($this->id)) {
			$this->database->table('category_file')
				->where('file_id',  $this->id)
				->delete();
		}
		foreach ($categories as $category) {
			$this->database->table('category_file')
				->insert(array(
					'category_id' => $category,
					'file_id' => $file->id
				));
		}
	}
	/**
	 * Funkce vytvoří soubor s jedinečným názevem a
	 * příponou podle MIME typu
	 * 
	 * @param strin $name jméno původního souboru
	 * @return string cesta k souboru
	 */
	private function getSubPath($name) {		
		$webname = $name->getSanitizedName();
		$folder = "downloads/";
		$filename = pathinfo($webname, PATHINFO_FILENAME);
		$extension =  pathinfo($webname, PATHINFO_EXTENSION);
		$finalname = $webname;
		$counter = 2;
		while (file_exists( \FileRepository::BASEDIR . $folder . $finalname )) {
			$finalname = $filename . '_' . $counter . '.' . $extension;
			$counter++;
		}
		return $folder . $finalname;
	}
}
