<?php

/**
 * Description of FileDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class FileDbMapper extends BaseDbMapper {
	
	private $userManager;
	
	/**
	 * 
	 * @param \Nette\Database\Context $database
	 * @param UserManager $userManager
	 */
	public function __construct(\Nette\Database\Context $database, UserManager $userManager) {
		parent::__construct($database);
		$this->userManager = $userManager;
	}

	public function getFile($id) {
		$row = $this->database->table('file')->get((int)$id);
		if(!$row) {
			throw new Nette\InvalidArgumentException("Soubor $id neexistuje");
		}
		$file = new File($row->id);
		$file->name = $row->name;
		$file->size = $row->size;
		$file->description = $row->description;
		$file->type = $row->type;
		$file->path = $row->path;
		$file->author = $this->userManager->load($row->author);
		return $file;
	}
	
	public function getFiles($repository) {
		$table = $this->database->table('file');
		$files = array();
		foreach ($table as $row) {
			$file = $this->getFile($row->id);
			$file->repository = $repository;
			$files[] = $file;
		}
		return $files;
	}

	public function getWhiteList() {
		$table = $this->database->table('whitelist');
		$whiteList = array();
		foreach ($table as $row) {
			$whiteList[] = $row->mime;
		}
		return $whiteList;
	}
	
	public function getIconName($type) {
		return $this->database->table('whitelist')
				->get($type)
				->path;
	}
}
