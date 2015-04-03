<?php

/**
 * Description of FileRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class FileRepository {
	
	const BASEDIR = "../files/";
	
	private $dbMapper;	
	
	/**
	 * 
	 * @param FileDbMapper $dbMapper
	 */
	public function __construct(FileDbMapper $dbMapper) {
		$this->dbMapper = $dbMapper;
	}
	
	public function getFile($id) {	
		$file = $this->dbMapper->getFile($id);
		$file->repository = $this;
		return $file;
	}
	
	public function getFiles() {
		return $this->dbMapper->getFiles($this);
	}
	
	public function getWhiteList() {
		return $this->dbMapper->getWhiteList();
	}
	
	public function getIconPath($type) {
		$file = $this->dbMapper->getIconName($type);
		return "/images/icons/" . $file;
	}
	
	
	
}
