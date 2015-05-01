<?php

/**
 * Description of RaceRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class RaceRepository {
	
	private $dbMapperFactory;	

	/**
	 * @param $dbMapperFactory
	 */
	public function __construct($dbMapperFactory) {
		$this->dbMapperFactory = $dbMapperFactory;
	}
	
	/**
	 * 
	 * @return RaceDbMapper
	 */
	private function getDbMapper() {
		return call_user_func($this->dbMapperFactory);
	}
	
	public function getRace($id) {
		$race = $this->getDbMapper()->getRace($id);
		$race->repository = $this;
		return $race;
	}
	
	public function getStatewideRound($season) {
		$race = $this->getDbMapper()->getStatewideRound($this, $season);	
		return $race;
	}
	
	public function getRegions() {
		return $this->getDbMapper()->getRegions();
	}
	
	public function getRaces($season) {
		return $this->getDbMapper()->getRaces($this, $season);
	}
	
	public function getRacesByWatch($watchId) {
		return $this->getDbMapper()->getRacesByWatch($this, $watchId);
	}
	
	public function getRacesToLogIn($season) {
		return $this->getDbMapper()->getRacesToLogIn($this, $season);
	}

	public function getRound($id) {
		return $this->getDbMapper()->getRound($id);
	}
	
	public function getRegion($id) {
		return $this->getDbMapper()->getRegion($id);
	}
	
	public function getMembersRange($id) {
		return $this->getDbMapper()->getMembersRange($id);
	}

	public function getAdvance($id) {
		$advanceId = $this->getDbMapper()->getAdvance($id);
		if ($advanceId) {
			return $this->getRace($advanceId);
		}
		return null;
	}
	
	public function getKey($raceId) {
		return $this->getDbMapper()->getKey($raceId);
	}

	public function getAuthor($id) {
		return $this->getDbMapper()->getAuthor($id);
	}


	public function getEditors($id) {
		return $this->getDbMapper()->getEditors($id);
	}

	public function getOrganizer($id) {
		return $this->getDbMapper()->getOrganizer($id);
	}
	
	public function getDataForForm($id) {
		return $this->getDbMapper()->getDataForForm($id);
	}
	
	public function getGuideAge($season) {
		return $this->getDbMapper()->getGuideAge($season);
	}
	
	public function getRunnerAge($season) {
		return $this->getDbMapper()->getRunnerAge($season);
	}
	
	public function getMinRunner($membersRange) {
		return $this->getDbMapper()->getMinRunner($membersRange);
	}
	
	public function getMaxRunner($membersRange) {
		return $this->getDbMapper()->getMaxRunner($membersRange);
	}
	
	public function getNumWatchs($raceId, $category = NULL) {
		return $this->getDbMapper()->getNumWatchs($raceId, $category);
	}
	
	public function getNumAdvance($raceId, $category) {
		return $this->getDbMapper()->getNumAdvance($raceId, $category);
	}
	
	public function getToken($raceId) {
		return $this->getDbMapper()->getToken($raceId);
	}
	
	public function setToken($raceId, $token) {
		$this->getDbMapper()->setToken($raceId, $token);
	}
	
	public function confirm($raceId, $token) {
		return $this->getDbMapper()->confirm($raceId, $token);
	}
}
