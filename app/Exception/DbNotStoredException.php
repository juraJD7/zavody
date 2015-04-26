<?php

/**
 * Description of DbNotStoredException
 *
 * @author 405245<405245@mail.muni.cz>
 */
class DbNotStoredException extends \Exception {
	
	public function __construct($message) {
		parent::__construct("Záznam není v databázi: " . $message, 0, NULL);
	}
}