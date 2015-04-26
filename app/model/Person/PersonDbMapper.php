<?php

/**
 * Description of PersonDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class PersonDbMapper extends BaseDbMapper {
	
	private $watchRepository;
	
	/**
	 * 
	 * @param \Nette\Database\Context $database
	 * @param \UserRepository $userRepository
	 * @param \UnitRepository $unitRepository
	 * @param $watchRepositoryFactory
	 */
	public function __construct(\Nette\Database\Context $database, \UserRepository $userRepository, \UnitRepository $unitRepository, $watchRepositoryFactory) {
		parent::__construct($database, $userRepository, $unitRepository);
		$this->watchRepository = $watchRepositoryFactory;
	}

	public function getRole($systemId, $raceId) {
		$result = $this->database->table('participant_race')
				->where('participant_id', $systemId)
				->where('race_id', $raceId)
				->fetch();
		if ($result) {
			return $this->database->table('role')
				->get($result->role)->name;
		} else {
			return null;
		}
	}
	
	/**
	 * 
	 * @param int $id 
	 */
	public function getParticipant($id) {
		$row = $this->database->table('participant')
				->get($id);
		if (!$row) {
			throw new DbNotStoredException("Účastník $id není v databázi");
		}
		$participant = new Person($row->person_id);
		$participant->systemId = $row->id;
		$participant->firstName = $row->firstname;
		$participant->lastName = $row->lastname;
		$participant->nickName = $row->nickname;
		$participant->sex = $row->sex;
		$participant->birthday = $row->birthday;
		$participant->unit = $this->unitRepository->getUnit($row->unit);
		$participant->watch = $this->watchRepositoryReference->getRgetWatch($row->watch);
	}

	/**
	 * 
	 * @param PersonRepository $repository
	 * @param int $watchId
	 * @param int $raceId
	 * @return array Person
	 */
	public function getPersonsByWatch(PersonRepository $repository, $watchId, $raceId) {
		$rowsMembers = $this->database->table('participant')
				->where('watch', $watchId);
		$members = array();
		foreach ($rowsMembers as $member) {
			$row = $this->database->table('participant_race')
					->where('participant_id', $member->id);
			if ($row) {
				$person = $this->getParticipant($member->id);
				$person->repository = $repository;				
				$members[] = $person;
			}
		}
		return $members;
	}
}
