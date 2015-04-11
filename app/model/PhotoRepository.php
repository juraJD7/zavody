<?php

/**
 * Description of PhotoRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PhotoRepository {
	
	const BASEDIR = "/photos/";
	const THUMBDIR = "/thumbs/";
	
	const THUMBSIZE = 180;
	
	private $dbMapper;	
	
	/**
	 * 
	 * @param PhotoDbMapper $dbMapper
	 */
	public function __construct(PhotoDbMapper $dbMapper) {
		$this->dbMapper = $dbMapper;
	}
	
	public function getPhoto($id) {	
		$photo = $this->dbMapper->getPhoto($id);
		$photo->repository = $this;
		return $photo;
	}
	
	public function getPublicPhotos($paginator = NULL) {
		return $this->dbMapper->getPublicPhotos($this, $paginator);
	}
	
	public function getWhiteList() {
		return $this->dbMapper->getWhiteList();
	}			
	
	public function deletePhoto($id) {
		unlink("." . $this->getPath($id));
		unlink("." . $this->getThumbPath($id));
		$this->dbMapper->deletePhoto($id);
	}
	
	public function countAllPublic() {
		return $this->dbMapper->countAllPublic();
	}
	
	public function getPath($id, $type = NULL) {
		if (is_null($type)) {
			$type = $this->dbMapper->getPhoto($id)->getType();
		}
		switch($type) {
			case "image/gif" : $extension = "gif"; break;
			case "image/png" : $extension = "png"; break;
			case "image/jpeg" : $extension = "jpeg"; break;
			default : throw new Exception("Neplatný obrázek");
		}
		return self::BASEDIR . $id . "." . $extension;
	}
	
	public function getThumbPath($id) {
		return self::THUMBDIR . $id . "_t.jpeg";
	}
}
