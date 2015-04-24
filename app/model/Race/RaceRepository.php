<?php

/**
 * Description of RaceRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class RaceRepository {
	
	private $dbMapper;	

	/**
	 * @param RaceDbMapper $dbMapper
	 */
	public function __construct(RaceDbMapper $dbMapper) {
		$this->dbMapper = $dbMapper;
	}
	
	public function getRace($id) {
		$race = $this->dbMapper->getRace($id);
		$race->repository = $this;
		return $race;
	}
	
	public function getStatewideRound($season) {
		$race = $this->dbMapper->getStatewideRound($this, $season);	
		return $race;
	}
	
	public function getRegions() {
		return $this->dbMapper->getRegions();
	}
	
	public function getRaces($season) {
		return $this->dbMapper->getRaces($this, $season);
	}
	
	public function getRacesByWatch($watchId) {
		return $this->dbMapper->getRacesByWatch($this, $watchId);
	}
	
	public function getRacesToLogIn($season) {
		return $this->dbMapper->getRacesToLogIn($this, $season);
	}

	public function getRound($id) {
		return $this->dbMapper->getRound($id);
	}
	
	public function getRegion($id) {
		return $this->dbMapper->getRegion($id);
	}
	
	public function getMembersRange($id) {
		return $this->dbMapper->getMembersRange($id);
	}

	public function getAdvance($id) {
		$advanceId = $this->dbMapper->getAdvance($id);
		if ($advanceId) {
			return $this->getRace($advanceId);
		}
		return null;
	}
	
	public function getKey($raceId) {
		return $this->dbMapper->getKey($raceId);
	}

	public function getAuthor($id) {
		return $this->dbMapper->getAuthor($id);
	}


	public function getEditors($id) {
		return $this->dbMapper->getEditors($id);
	}

	public function getOrganizer($id) {
		return $this->dbMapper->getOrganizer($id);
	}
	
	public function getDataForForm($id) {
		return $this->dbMapper->getDataForForm($id);
	}
	
	public function getGuideAge($season) {
		return $this->dbMapper->getGuideAge($season);
	}
	
	public function getRunnerAge($season) {
		return $this->dbMapper->getRunnerAge($season);
	}
	
	public function getMinRunner($membersRange) {
		return $this->dbMapper->getMinRunner($membersRange);
	}
	
	public function getMaxRunner($membersRange) {
		return $this->dbMapper->getMaxRunner($membersRange);
	}
	
	public function getNumWatchs($raceId, $category = NULL) {
		return $this->dbMapper->getNumWatchs($raceId, $category);
	}
	
	public function getNumAdvance($raceId, $category) {
		return $this->dbMapper->getNumAdvance($raceId, $category);
	}
}
