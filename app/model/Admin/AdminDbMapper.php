<?php

/**
 * Description of AdminDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class AdminDbMapper extends BaseDbMapper {
	
	public function getAllCompetitions() {
		return $this->database->table('competition');
	}
	
	public function getAllSeasons() {
		$table = $this->database->table('season')
				->order('id DESC');
		$seasons = array();
		foreach ($table as $row) {
			$season = new Season($row->id);
			$season->year = $row->year;
			$season->competition = $this->getCompetition($row->competition);
			$season->runnerAge = $row->runner_age;
			$season->guideAge = $row->guide_age;
			$seasons[] = $season;
		}
		return $seasons;
	}
	
	public function getCompetition($id) {
		$row = $this->database->table('competition')
				->get($id);
		$competition = new Competition($id);
		$competition->name = $row->name;
		$competition->short = $row->short;
		return $competition;
	}
	
	public function getDefaultSeason() {
		return $this->database->table('setting')
				->get('season')
				->value;
	}
}
