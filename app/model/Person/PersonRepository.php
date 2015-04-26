<?php

/**
 * Description of PersonRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PersonRepository {
		
	private $isMapper;
	private $dbMapperFactory;
	
	/**
	 * 
	 * @param PersonIsMapper $isMapper
	 * @param $dbMapperFactory
	 */
	public function __construct(PersonIsMapper $isMapper, $dbMapperFactory) {
		$this->isMapper = $isMapper;
		$this->dbMapperFactory = $dbMapperFactory;
	}
	
	/**
	 * 
	 * @return PersonDbMapper
	 */
	private function getDbMapper() {
		return call_user_func($this->dbMapperFactory);
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
		return $this->getDbMapper()->getRole($systemId, $raceId);
	}	
	
	public function getPersonsByWatch($watchId, $raceId) {
		return $this->getDbMapper()->getPersonsByWatch($this, $watchId, $raceId);
	}
	
}
