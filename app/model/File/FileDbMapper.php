<?php

/**
 * FileDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class FileDbMapper extends BaseDbMapper {

	/**
	 * Vrátí soubor
	 * 
	 * @param int $id
	 * @return \File
	 * @throws Race\DbNotStoredException
	 */
	public function getFile($id) {
		$row = $this->database->table('file')->get((int)$id);
		if(!$row) {
			throw new Race\DbNotStoredException("Soubor $id neexistuje");
		}
		$file = new File($row->id);
		$file->name = $row->name;
		$file->size = $row->size;
		$file->description = $row->description;
		$rowFileType = $this->database->table('whitelist')->get($row->type);
		$file->type = new FileType($rowFileType->mime, $rowFileType->title, $rowFileType->path);
		$file->path = $row->path;
		$file->author = $this->userRepository->getUser($row->author);
		$file->competition = $row->competition;
		return $file;
	}
	
	/**
	 * Vrátí všechny soubory
	 * 
	 * @param FileRepository $repository
	 * @param Nette\Utils\Paginator $paginator
	 * @param int $category Omezí výběr na kategorii
	 * @return Files[]
	 */
	public function getFiles($repository, $paginator, $category = null) {
		$table = $this->database->table('file')
				->where('competition', $this->competition)
				->order('id DESC')
				->limit($paginator->getLength(), $paginator->getOffset());
		//pokud je zadaná kategorie
		if (!empty($category)) {
			$join =  $this->database->table('category_file')
				->where('category_id', $category);				
			$fileIds = array();
			foreach ($join as $row) {
				$fileIds[] = $row->file_id;
			}
			$table = $table->where('id IN', $fileIds);
		}
		$table = $table->limit($paginator->getLength(), $paginator->getOffset());
		$files = array();
		foreach ($table as $row) {
			$file = $this->getFile($row->id);
			$file->repository = $repository;
			$files[] = $file;
		}		
		return $files;
	}
	
	/**
	 * Vrátí soubory podle autora
	 * 
	 * @param FileRepository $repository
	 * @param Nette\Utils\Paginator $paginator
	 * @param int $userId
	 * @return File[]
	 */
	public function getFilesByAuthor(FileRepository $repository, $paginator, $userId) {
		$table = $this->database->table('file')
				->where('author', $userId)
				->where('competition', $this->competition)
				->order('id DESC')
				->limit($paginator->getLength(), $paginator->getOffset());	
		$files = array();
		foreach ($table as $row) {
			$file = $this->getFile($row->id);
			$file->repository = $repository;
			$files[] = $file;
		}		
		return $files;
	}

	/**
	 * Vrátí pole povolených MIME typů
	 * 
	 * @return string[]
	 */
	public function getWhiteList() {
		$table = $this->database->table('whitelist');
		$whiteList = array();
		foreach ($table as $row) {
			$whiteList[] = $row->mime;
		}
		return $whiteList;
	}
	
	/**
	 * Vrátí pole všech povolených druhů souboru
	 * 
	 * @return \FileType[]
	 */
	public function getFileTypes() {
		$table = $this->database->table('whitelist');
		$fileTypes = array();
		foreach ($table as $row) {
			$fileTypes[] = new FileType($row->mime, $row->title, $row->path);			
		}
		return $fileTypes;
	}
	
	/**
	 * Smaže druh souboru podle zadaného mime typu
	 * 
	 * @param string $mime Mime typ souboru
	 */
	public function deleteFileType($mime) {
		$row = $this->database->table('whitelist')
			->where('mime', $mime);
		// smazání ikony, pokud existuje
		$path = $row->fetch()->path;
		if ($path != "default.png") {
			unlink( "./" . \File::ICONDIR . $path);
		}
		$files = $this->database->table('file')
				->where('type', $mime);
		//smazání všech nově nepovolených souborů
		foreach ($files as $file) {
			unlink (\FileRepository::BASEDIR . $file->path);
		}
		$files->delete();
		//smazání druhu souboru
		$row->delete();
	}	
	
	/**
	 * Vrátí seznam kategorií podle souboru
	 * 
	 * @param int $id ID souboru
	 * @return \Category[]
	 */
	public function getCatogoriesByFile($id) {
		$join =  $this->database->table('category_file')
					->where('file_id', $id);
		$categories = array();
		foreach ($join as $category) {			
			$row = $this->database->table('category')
					->where('id', $category->category_id)
					->fetch();
			$category = new Category($row->id);
			$category->name = $row->name;
			$category->short = $row->short;
			$categories[] = $category;
		}
		return $categories;
	}	
	
	/**
	 * Smaže soubor
	 * 
	 * @param int $id
	 */
	public function deleteFile($id) {
		$this->database->table('category_file')
				->where('file_id', $id)
				->delete();
		$this->database->table('file')
				->where('id', $id)
				->delete();
	}
	
	/**
	 * Vrátí počet souborů
	 * 
	 * @param int $category Omezí výběr na kategorii
	 * @return int
	 */
	public function countAll($category) {
		if (!empty($category)) {
			$table = $this->database->table('category_file')
					->where('category_id',$category);
			$counter = 0;
			//prochází se pouze soubory se zvolenou kategorií
			foreach ($table as $row) {
				$file = $this->database->table('file')
						->get($row->file_id);
				//pokud patří do aktuální soutěže, započítá se
				if ($file->competition == $this->competition) {
					$counter++;
				}						
			}
			return $counter;
		}	
		//pokud není zadána kategorie, sečtou se všechyn záznamy aktuální soutěže
		return $this->database->table('file')
				->where('competition', $this->competition)
				->count();
	}
	
	/**
	 * Vrátí počet souborů podle autora
	 * 
	 * @param int $userId
	 * @return int
	 */
	public function countAllAuthor($userId) {
		return $this->database->table('file')
				->where('author', $userId)
				->where('competition', $this->competition)
				->count();
	}
}
