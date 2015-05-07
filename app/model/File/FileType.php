<?php

/**
 * Description of FileType
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class FileType extends Nette\Object {
	
	private $mime;
	private $title;
	private $path;
	
	public function __construct($mime, $title, $path) {
		$this->mime = $mime;
		$this->title = $title;
		$this->path = $path;
	}
	
	public function getMime() {
		return $this->mime;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public function getPath() {
		return File::ICONDIR . $this->path;
	}
}
