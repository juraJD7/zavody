<?php

/**
 * Description of PhotoDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PhotoDbMapper extends BaseDbMapper {
	
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

	public function getPhoto($id) {
		$row = $this->database->table('photo')->get((int)$id);
		if(!$row) {
			throw new Nette\InvalidArgumentException("Fotka $id neexistuje");
		}
		$photo = new Photo($row->id);
		$photo->description = $row->description;
		$photo->race = $row->race;
		$photo->public = $row->is_public;
		$photo->created = $row->created;
		$photo->height = $row->height;
		$photo->width = $row->width;
		$photo->size = $row->size;
		$photo->type = $row->type;
		$photo->author = $this->userManager->load($row->author);
		return $photo;
	}
	
	public function getPublicPhotos($repository, $paginator = null) {
		$table = $this->database->table('photo')
				->where('is_public', TRUE)
				->order('id DESC')
				->limit($paginator->getLength(), $paginator->getOffset());	
		$photos = array();
		foreach ($table as $row) {
			$photo = $this->getPhoto($row->id);
			$photo->repository = $repository;
			$photos[] = $photo;
		}		
		return $photos;
	}
	
	public function deletePhoto($id) {		
		$this->database->table('photo')
				->where('id', $id)
				->delete();
	}
	
	public function countAllPublic() {				
		return $this->database->table('photo')
				->where('is_public', TRUE)
				->count();
	}
}
