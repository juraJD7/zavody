<?php

/**
 * Description of PersonRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PersonRepository {
		
	private $isMapper;
	private $dbMapper;
	
	/**
	 * 
	 * @param PersonIsMapper $isMapper
	 * @param PersonDbMapper $dbMapper
	 */
	public function __construct(PersonIsMapper $isMapper, PersonDbMapper $dbMapper) {
		$this->isMapper = $isMapper;
		$this->dbMapper = $dbMapper;
	}
	
	/**
	 * Vrátí účastníka podle id
	 * 
	 * @param int $personId
	 */
	public function getPerson($personId) {
		$person = $this->isMapper->getPerson($personId);
		$person->repository = $this;
		return $person;
	}
	
	public function getPersonsByUnit($idUnit) {
		return $this->isMapper->getPersonsByUnit($this, $idUnit);
	}
	
	public function getRole($systemId, $raceId) {
		return $this->dbMapper->getRole($systemId, $raceId);
	}	
	
	public function getPersonsByWatch($watchId, $raceId) {
		return $this->dbMapper->getPersonsByWatch($this, $watchId, $raceId);
	}
	
}
