<?php

namespace App\Forms;

use Nette,
	Nette\Application\UI\Form,
	Nette\Security\User;

/**
 * Description of PhotoUploadFormFactory
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PhotoUploadFormFactory extends BaseFormFactory {
	
	private $photoRepository;
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param \Nette\Database\Context $database
	 * @param \PhotoRepository $photoRepository
	 */
	public function __construct(\Skautis\Skautis $skautIS, \Nette\Database\Context $database, \PhotoRepository $photoRepository) {
		parent::__construct($skautIS, $database);
		$this->photoRepository = $photoRepository;
	}

	/**
	 * @return Form
	 */
	public function create()
	{				
		$form = new Form;

		$form->addUpload('files', 'Fotky:', TRUE);
		
		$form->addCheckbox('isPublic', 'Zveřejnit fotky');

		$form->addSubmit('send', 'Uložit');

		$form->onSuccess[] = array($this, 'formSucceeded');
		
		$renderer = $form->getRenderer();
		$this->addBootstrapRendering($renderer, $form);
		
		return $form;
	}
	
	public function formSucceeded(Form $form, $values)
	{
		foreach ($values->files as $file) {
			if($file->isOk() && $file->isImage()) {
				$user = $this->skautIS->usr->UserDetail()->ID;
				$data = array(
					"is_public" => $values->isPublic,
					"author" => $user,
					"created" => date('Y-m-d H:i:s'),
					"height" => $file->getImageSize()[1],
					"width" => $file->getImageSize()[0],
					"size" => $file->getSize(),
					"type" => $file->getContentType()
				);
				$inserted = $this->database->table('photo')->insert($data);				
				$path = $this->getPath($inserted->id, $file->getContentType());	
				$file->move($path);
				$thumb = $this->createThumb($file);				
				imagejpeg($thumb, $this->getThumbPath($inserted->id));
			} else {
				$form->addError("Při nahrávání se objevila chyba.");
			}
		}
	}
	
	private function getThumbPath($id) {
		return "./" . \PhotoRepository::THUMBDIR . $id . "_t.jpeg";
	}

	private function getPath($id, $mimeType) {
		$extension = substr($mimeType, strrpos($mimeType, '/') + 1);
		return "./" . \PhotoRepository::BASEDIR . $id . "." . $extension;
	}
	
	private function createThumb(Nette\Http\FileUpload $file) {
		$size = $file->getImageSize();
		if ($size[0] > $size[1]) {
			$width = \PhotoRepository::THUMBSIZE;
			$height = $size[1] * (\PhotoRepository::THUMBSIZE / $size[0]);
		} else {
			$height = \PhotoRepository::THUMBSIZE;
			$width = $size[0] * (\PhotoRepository::THUMBSIZE / $size[1]);
		}
		$tmp = imagecreatetruecolor($width, $height);
		$thumb = imagecreatetruecolor(\PhotoRepository::THUMBSIZE, \PhotoRepository::THUMBSIZE);
		$bg = imagecolorallocate($thumb, 255, 255, 255);
		imagefill($thumb, 0, 0, $bg);
		switch ($file->getContentType()) {
			case "image/gif" : $imgCreateFrom = "ImageCreateFromGIF"; break;
			case "image/png" : $imgCreateFrom = "ImageCreateFromPNG"; break;
			case "image/jpeg" : $imgCreateFrom = "ImageCreateFromJPEG"; break;
			default : throw new Exception("Neplatný obrázek");
		}		
		$oldimage = $imgCreateFrom($file->temporaryFile);
		imagecopyresized($tmp, $oldimage, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
		imagecopy($thumb, $tmp, 0, (\PhotoRepository::THUMBSIZE - $height) / 2, 0, 0, $width, $height);
		return $thumb;
	}
}
