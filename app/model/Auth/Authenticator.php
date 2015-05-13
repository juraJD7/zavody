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
	
	/**
	 *
	 * @var \RaceRepository
	 */
	private $raceRepository;

    function __construct(Nette\Database\Context $database, \SkautIS\SkautIS $skautIS, RaceRepository $raceRepository)
    {
        $this->database = $database;
		$this->skautIS = $skautIS;
		$this->raceRepository = $raceRepository;
    }
	
	/**
	 * Provede autentizaci
	 * @return Nette\Security\Identity
	 * @throws \Skautis\Wsdl\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		if ($this->skautIS->getUser()->isLoggedIn()) {
			$userID = $credentials[0]->ID;
			$admin = $this->database->table('user')
					->where('is_admin', TRUE)
					->get($userID);

			if ($admin) {
				return new NS\Identity($admin->id_user, "admin");
			}
			$races = $this->raceRepository->getRacesByEditor($userID);
			if (!empty($races)) {				
				$data = array();
				foreach ($races as $race) {
					$data[] = $race->id;
				}
				return new NS\Identity($userID, "raceManager", array("races" => $data));
			}
			
			return new NS\Identity($userID, "common");		
		} else {
			throw new \Nette\Application\ApplicationException("Pokus o získání identity bez přihlášení ve SkautISu.");
		}        
	}
}