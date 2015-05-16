<?php

/**
 * PhotoDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PhotoDbMapper extends BaseDbMapper {
	
	/**
	 * Vrátí fotografii
	 * 
	 * @param int $id
	 * @return \Photo
	 * @throws Race\DbNotStoredException
	 */
	public function getPhoto($id) {
		$row = $this->database->table('photo')->get((int)$id);
		if(!$row) {
			throw new Race\DbNotStoredException("Fotka $id neexistuje");
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
		$photo->author = $this->userRepository->getUser($row->author);
		return $photo;
	}
	
	/**
	 * Vrátí veřejné fotky
	 * 
	 * @param PhotoRepository $repository
	 * @param Nette\Utils\Paginator $paginator omezení stránkováním
	 * @return Photo[]
	 */
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
	
	/**
	 * Vrátí fotografie podle autora
	 * 
	 * @param PhotoRepository $repository
	 * @param Nette\Utils\Paginator $paginator omezení stránkováním
	 * @param int $userId
	 * @return Photo[]
	 */
	public function getPhotosByAuthor(PhotoRepository $repository, $paginator, $userId) {
		$table = $this->database->table('photo')
				->where('author', $userId)
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
	
	/**
	 * Vrátí fotografie podle závodu
	 * 
	 * @param PhotoRepository $repository
	 * @param Nette\Utils\Paginator $paginator omezení stránkováním
	 * @param int $raceId
	 * @return Photo[]
	 */
	public function getPhotosByRace(PhotoRepository $repository, $paginator, $raceId) {
		$table = $this->database->table('photo')
				->where('race', $raceId)
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
	
	/**
	 * Smaže fotografii
	 * 
	 * @param int $id
	 */
	public function deletePhoto($id) {		
		$this->database->table('photo')
				->where('id', $id)
				->delete();
	}
	
	/**
	 * Vrátí počet veřejných fotografií
	 * 
	 * @return int
	 */
	public function countAllPublic() {				
		return $this->database->table('photo')
				->where('is_public', TRUE)
				->count();
	}
	
	/**
	 * Vrátí počet fotografií podle autora
	 * 
	 * @return int
	 */
	public function countAllAuthor($userId) {				
		return $this->database->table('photo')
				->where('author', $userId)
				->count();
	}
	
	/**
	 * Vrátí počet fotografií ze závodu
	 * 
	 * @return int
	 */
	public function countAllRace($raceId) {				
		return $this->database->table('photo')
				->where('race', $raceId)
				->count();
	}
}
