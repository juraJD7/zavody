<?php

/**
 * Description of UnitDbMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class UnitDbMapper {
	
	/**
	 *
	 * @var \Nette\Database\Context
	 */
	protected $database;
	
	public function __construct(\Nette\Database\Context $database) {		
		$this->database = $database;		
	}
	
	public function save(Unit $unit) {
		$data = array(
			"id" => (int) $unit->id,
			"registration_number" => $unit->registrationNumber,
			"name" => $unit->displayName
		);
		$row = $this->database->table('unit')->where('id', $unit->id)->fetch();
		if ($row) {
			return $row->update($data);
		} else {
			return $this->database->table('unit')->insert($data);
		}
	}
	
}
