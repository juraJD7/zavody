<?php

namespace App\Forms;

use Nette,
	Nette\Application\UI\Form;

/**
 * PhotoUploadFormFactory
 * 
 * Továrna pro nahrávání fotografií
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PhotoUploadFormFactory extends BaseFormFactory {
	
	private $photoRepository;
	private $race;
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param \Nette\Database\Context $database
	 * @param \PhotoRepository $photoRepository
	 */
	public function __construct(\Skautis\Skautis $skautIS, \Nette\Database\Context $database, \PhotoRepository $photoRepository) {
		parent::__construct($skautIS, $database);
		$this->photoRepository = $photoRepository;
		$this->race = NULL;
	}
	
	public function setRace($race) {
		$this->race = $race;
	}

	/**
	 * @return Form
	 */
	public function create()
	{				
		$form = new Form;

		$form->addUpload('files', 'Fotky:', TRUE);
		if ($this->race) {
			$form->addCheckbox('isPublic', 'Zveřejnit fotky');
		}
		$form->addHidden('race', $this->race);
		$form->addSubmit('send', 'Uložit');

		$form->onSuccess[] = array($this, 'formSucceeded');
		
		$renderer = $form->getRenderer();
		$this->addBootstrapRendering($renderer, $form);
		
		return $form;
	}
	
	public function formSucceeded(Form $form, $values)
	{
		$post = $form->getHttpData();		
		$values->isPublic = empty($post["race"]) ? 1 : isset($post["isPublic"]);
		foreach ($values->files as $file) {
			// kontrola, zda se jedná o obrázek, akceptuje se PNG, JPG a GIF (podle MIME)
			if($file->isOk() && $file->isImage()) {
				
				$values->race = $values->race ?: NULL;
				$user = $this->skautIS->usr->UserDetail()->ID;
				$data = array(
					"is_public" => $values->isPublic,
					"author" => $user,
					"created" => date('Y-m-d H:i:s'),
					"height" => $file->getImageSize()[1],
					"width" => $file->getImageSize()[0],
					"size" => $file->getSize(),
					"type" => $file->getContentType(),
					"race" => $values->race
				);
				$inserted = $this->database->table('photo')->insert($data);	
				//vytvoření cesty k souboru a uložení do filesystému
				$path = $this->getPath($inserted->id, $file->getContentType());	
				$file->move($path);
				//vytvoření a uložení náhledu
				$thumb = $this->createThumb($file);				
				imagejpeg($thumb, $this->getThumbPath($inserted->id));
			} else {
				$form->addError("Při nahrávání se objevila chyba.");
			}
		}
	}
	/**
	 * Vytvoří cestu k náhledu podle ID fotografie
	 * 
	 * @param int $id
	 * @return $string
	 */
	private function getThumbPath($id) {
		return "./" . \PhotoRepository::THUMBDIR . $id . "_t.jpeg";
	}
	
	/**
	 * Vytvoří cestu k obrázku podle MIME type a ID obrázku
	 * 
	 * @param int $id
	 * @param string $mimeType
	 * @return $string výsledná cesta obrázku
	 */
	private function getPath($id, $mimeType) {
		$extension = substr($mimeType, strrpos($mimeType, '/') + 1);
		return "./" . \PhotoRepository::BASEDIR . $id . "." . $extension;
	}
	
	/**
	 * Vytvoří náhled obrázku
	 * 
	 * @param Nette\Http\FileUpload $file
	 * @return file
	 * @throws Exception pokud je neplatný obrázek
	 */
	private function createThumb(Nette\Http\FileUpload $file) {
		// test, zda se jedná o podprovaný formát obrázku
		switch ($file->getContentType()) {
			case "image/gif" : $imgCreateFrom = "ImageCreateFromGIF"; break;
			case "image/png" : $imgCreateFrom = "ImageCreateFromPNG"; break;
			case "image/jpeg" : $imgCreateFrom = "ImageCreateFromJPEG"; break;
			default : throw new Exception("Neplatný obrázek");
		}
		$size = $file->getImageSize();
		//nastavení rozměrů náhledu na výšku nebo na šířku
		if ($size[0] > $size[1]) {
			$width = \PhotoRepository::THUMBSIZE;
			$height = $size[1] * (\PhotoRepository::THUMBSIZE / $size[0]);
		} else {
			$height = \PhotoRepository::THUMBSIZE;
			$width = $size[0] * (\PhotoRepository::THUMBSIZE / $size[1]);
		}
		//vytvoření zatím prázdného náhledu
		$tmp = imagecreatetruecolor($width, $height);
		$thumb = imagecreatetruecolor(\PhotoRepository::THUMBSIZE, \PhotoRepository::THUMBSIZE);
		$bg = imagecolorallocate($thumb, 255, 255, 255);
		imagefill($thumb, 0, 0, $bg);			
		//zkopírování původního obrázku do náhledu
		$oldimage = $imgCreateFrom($file->temporaryFile);
		imagecopyresized($tmp, $oldimage, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
		imagecopy($thumb, $tmp, 0, (\PhotoRepository::THUMBSIZE - $height) / 2, 0, 0, $width, $height);
		return $thumb;
	}
}
