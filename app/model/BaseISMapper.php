<?php

/**
 * Description of BaseISMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class BaseISMapper {
	
	/**
	 *
	 * @var \Skautis\Skautis 
	 */
	protected $skautIS;
	
	/**
	 * @var \UnitRepository
	 */
	protected $unitRepository;
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param \UnitRepository $unitRepository
	 */
	public function __construct(\Skautis\Skautis $skautIS, \UnitRepository $unitRepository) {
		$this->skautIS = $skautIS;
		$this->unitRepository = $unitRepository;
	}
	
}
