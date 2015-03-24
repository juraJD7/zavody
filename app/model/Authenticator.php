<?php

/**
 * Description of Authentizator
 *
 * @author Jiří Doušek <405245@mail.muni.cz>
 */

use Nette\Security as NS;

class Authenticator extends Nette\Object implements Nette\Security\IAuthenticator
{
	
	/**
	 *
	 * @var \Nette\Database\Context
	 */
	private $database;
	
	/**
	 *
	 * @var \Skautis\Skautis
	 */
	private $skautIS;

    function __construct(Nette\Database\Context $database, \SkautIS\SkautIS $skautIS)
    {
        $this->database = $database;
		$this->skautIS = $skautIS;
    }
	
	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws \Skautis\Wsdl\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		if ($this->skautIS->getUser()->isLoggedIn()) {
			$userID = $credentials[0]->ID;
			$admin = $this->database->table('administrators')
				->get($userID);

			if ($admin) {
				return new NS\Identity($admin->id, "admin");
			}

			$raceManagers = $this->database->table('editors_races')
					->where('user_id',$userID);
			if ($raceManagers) {
				$races = array();
				foreach ($raceManagers as $manager) {
					$races[] = $manager->race_id;
				}
				return new NS\Identity($userID, "raceManager", array("races" => $races));
			}
			
			return new NS\Identity($userID, "common");		
		} else {
			throw new \Skautis\Wsdl\AuthenticationException("Pokus o získání identity bez přihlášení ve SkautISu.");
		}        
	}
}