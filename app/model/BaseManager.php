<?php

/**
 * Description of BaseManager
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class BaseManager {
	
	/**
	 *
	 * @var \Skautis\Skautis
	 */
	protected $skautIS;
	
	/**
	 *
	 * @var \Nette\Database\Context
	 */
	protected $database;

	/**
	 * 
	 * @param Skautis\Skautis $skautIS
	 * @param Nette\Database\Context $database
	 */
	public function __construct(Skautis\Skautis $skautIS, Nette\Database\Context $database) {
		$this->skautIS = $skautIS;
		$this->database = $database;
	}
}
