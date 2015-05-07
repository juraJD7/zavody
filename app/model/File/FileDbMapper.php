<?php

/**
 * Description of FileDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class FileDbMapper extends BaseDbMapper {

	public function getFile($id) {
		$row = $this->database->table('file')->get((int)$id);
		if(!$row) {
			throw new Nette\InvalidArgumentException("Soubor $id neexistuje");
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
	
	public function getFiles($repository, $paginator, $category = null) {	
		
		if (!is_null($category) && !empty($category)) {
			return $this->getFilesByCategory($repository, $paginator, $category);
		}
		$table = $this->database->table('file')
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

	public function getWhiteList() {
		$table = $this->database->table('whitelist');
		$whiteList = array();
		foreach ($table as $row) {
			$whiteList[] = $row->mime;
		}
		return $whiteList;
	}
	
	public function getFileTypes() {
		$table = $this->database->table('whitelist');
		$fileTypes = array();
		foreach ($table as $row) {
			$fileTypes[] = new FileType($row->mime, $row->title, $row->path);			
		}
		return $fileTypes;
	}
	
	public function deleteFileType($mime) {
		$row = $this->database->table('whitelist')
			->where('mime', $mime);
		$path = $row->fetch()->path;
		if ($path != "default.png") {
			unlink( "./" . \File::ICONDIR . $path);
		}
		$files = $this->database->table('file')
				->where('type', $mime);
		foreach ($files as $file) {
			unlink (\FileRepository::BASEDIR . $file->path);
		}
		$files->delete();
		$row->delete();
	}	
	
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
	
	public function getFilesByCategory($repository,  $paginator, $id) {
		$join =  $this->database->table('category_file')
				->where('category_id', $id);				
		$fileIds = array();
		foreach ($join as $row) {
			$fileIds[] = $row->file_id;
		}
		$table = $this->database->table('file')
				->where('id IN', $fileIds)
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
	
	public function deleteFile($id) {
		$this->database->table('category_file')
				->where('file_id', $id)
				->delete();
		$this->database->table('file')
				->where('id', $id)
				->delete();
	}
	
	public function countAll($category) {
		if (!is_null($category) && !empty($category)) {
			$table = $this->database->table('category_file')
					->where('category_id',$category);
			$counter = 0;
			foreach ($table as $row) {
				$file = $this->database->table('file')
						->get($row->file_id);
				if ($file->competition == $this->competition) {
					$counter++;
				}						
			}
			return $counter;
		}		
		return $this->database->table('file')
				->where('competition', $this->competition)
				->count();
	}
	
	public function countAllAuthor($userId) {
		return $this->database->table('file')
				->where('author', $userId)
				->where('competition', $this->competition)
				->count();
	}
}
