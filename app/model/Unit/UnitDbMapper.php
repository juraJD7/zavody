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
	
	/**
	 * 
	 * @param \Nette\Database\Context $database
	 */
	public function __construct(\Nette\Database\Context $database) {		
		$this->database = $database;		
	}
	
	/**
	 * Vrátí jednotku
	 * 
	 * @param int $id
	 * @return \Unit
	 * @throws Race\DbNotStoredException
	 */
	public function getUnit($id) {
		$row = $this->database->table('unit')->get($id);
		if(!$row) {
			throw new Race\DbNotStoredException("Jednotka $id nenalezena.");
		}
		$unit = new Unit($row->id);
		$unit->registrationNumber = $row->registration_number;
		$unit->displayName = $row->name;
		$unit->email = $row->email;
		$unit->telephone = $row->telephone;
		$unit->unitType = $row->unit_type;
		return $unit;
	}

	/**
	 * Uloží zadanou hlídku do databáze
	 * 
	 * @param Unit $unit
	 * @return int Počet ovlivněných řádků
	 */
	public function save(Unit $unit) {
		$data = array(
			"id" => (int) $unit->id,
			"registration_number" => $unit->registrationNumber,
			"name" => $unit->displayName,
			"unit_type" => $unit->unitType
		);
		if ($unit->email) {
			$data["email"] = $unit->email;
		}
		if ($unit->telephone) {
			$data["telephone"] = $unit->telephone;
		}
		$row = $this->database->table('unit')->where('id', $unit->id)->fetch();
		if ($row) {
			return $row->update($data);
		} else {
			return $this->database->table('unit')->insert($data);
		}
	}
	
}
