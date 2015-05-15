<?php

/**
 * SessionExpiredException
 * 
 * Výjmka, pokud jsou dotazována session data, která již vypršela
 *
 * @author 405245 <405245@mail.muni.cz>
 */
class SessionExpiredException extends \Exception {
	
	public function __construct($message) {
		parent::__construct($message, 0, NULL);
	}
}
