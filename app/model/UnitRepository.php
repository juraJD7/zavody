<?php

/**
 * Description of UnitRepository
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class UnitRepository {
	
	private $isMapper;
	private $dbMapper;
	
	/**
	 * 
	 * @param UnitISMapper $isMapper
	 * @param UnitDbMapper $dbMapper
	 */
	public function __construct(UnitISMapper $isMapper, UnitDbMapper $dbMapper) {
		$this->isMapper = $isMapper;
		$this->dbMapper = $dbMapper;
	}
	
	public function getUnit($id) {
		$unit = $this->isMapper->getUnit($id);
		$unit->repository = $this;		
		return $unit;
	}
	
	public function getSubordinateUnits($id) {
		return $this->isMapper->getSubordinateUnits($this, $id);
	}
	
	public function getEmail($id) {
		return $this->isMapper->getEmail($id);
	}
	
	public function getTelephone($id) {
		return $this->isMapper->getTelephone($id);
	}
	
}
