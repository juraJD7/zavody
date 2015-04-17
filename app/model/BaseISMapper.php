<?php

/**
 * Description of BaseISMapper
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class BaseISMapper {
	
	protected $skautIS;
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 */
	public function __construct(\Skautis\Skautis $skautIS) {
		$this->skautIS = $skautIS;
	}
	
}
