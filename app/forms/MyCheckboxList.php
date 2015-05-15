<?php

namespace Nette\Forms\Controls;

/**
 * MyCheckBoxList
 * 
 * Vlastní vykreslení CheckBoxListu s checkboxy
 * na jednom řádku (separátorem nezalomitelná mezera 
 * místo výchozího tagu <br />
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class MyCheckboxList extends CheckboxList{
	
	public function __construct($label = NULL, array $items = NULL) {
		parent::__construct($label, $items);
		$this->separator = " &nbsp; ";
	}
	
}
