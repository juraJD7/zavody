<?php

namespace App\Forms;

use Nette,
	Nette\Application\UI\Form,
	Nette\Security\User;

/**
 * Description of FileFormFactory
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
		$form = new Form;
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
			$whiteList = $this->fileRepository->getWhiteList();
			$user = $this->skautIS->usr->UserDetail()->ID;	
			$type = $values->file->getContentType();
			if(in_array($type, $whiteList)) {
				if (!is_null($this->id)) {
					//detele old file
					$file = $this->database->table('file')->get($this->id);
					unlink(\FileRepository::BASEDIR . $file->path);
				}
				$subPath = $this->getSubPath($values->file);								
				$path = \FileRepository::BASEDIR . "$subPath";
				//var_dump($path);exit;
				$values->file->move($path);
				$data = array(
					'path' => $subPath,
					'name' => $values->name,
					'description' => $values->description,
					'type' => $type,
					'size' => $values->file->getSize(),
					'author' => $user
				);
				if (!is_null($this->id)) {
					$file->update($data);
				} else {
					$this->database->table('file')->insert($data);
				}
			} else {
				$form->addError("Nelze nahrát souboru typu $type");
			}
		} else {
			$error = $values->file->getError();
			if (!is_null($this->id) && $error == UPLOAD_ERR_NO_FILE) {
				$file = $this->database->table('file')->get($this->id);
				$file->update(array(					
					'name' => $values->name,
					'description' => $values->description,
				));
			} else {
				$form->addError("Při nahrávání se objevila chyba. Kod: $error");
			}
		}	
	}
	
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
