<?php

namespace App\Forms;

use Nette;

/**
 * Description of BaseFormFactory
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class BaseFormFactory extends Nette\Object
{
	/**
	 * @var \Skautis\Skautis
	 */
	protected $skautIS;
	
	/**
	 * @var \Nette\Database\Context
	 */
	protected $database;
	
	protected $season;
	
	/**
	 * 
	 * @param \Skautis\Skautis $skautIS
	 * @param \Nette\Database\Context $database
	 */
	public function __construct(\Skautis\Skautis $skautIS, \Nette\Database\Context $database)
	{
		$this->skautIS = $skautIS;
		$this->database = $database;
		if (isset($_COOKIE["season"])) {
			$this->season = $_COOKIE["season"];
		} else {
			$this->season = $this->database->table('setting')->get('season')->value;					
		}
	}
	
	/**
	 * Přidává základní formátování formulářům podle Bootsteap
	 * 
	 * převzato z https://github.com/nette/forms/blob/master/examples/bootstrap3-rendering.php
	 * 
	 * @param IFormRenderer $renderer
	 * @param Application\UI\Form $form
	 */
	protected function addBootstrapRendering(&$renderer, &$form) {
		//bootstrap rendering		
		$renderer->wrappers['controls']['container'] = NULL;
		$renderer->wrappers['pair']['container'] = 'div class=form-group';
		$renderer->wrappers['pair']['.error'] = 'has-error';
		$renderer->wrappers['control']['container'] = 'div class=col-sm-9';
		$renderer->wrappers['label']['container'] = 'div class="col-sm-3 control-label"';
		$renderer->wrappers['control']['description'] = 'span class=help-block';
		$renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';
		
		// make form and controls compatible with Twitter Bootstrap
		$form->getElementPrototype()->class('form-horizontal');
		foreach ($form->getControls() as $control) {
			if ($control instanceof Controls\Button) {
				$control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-default');
				$usedPrimary = TRUE;
			} elseif ($control instanceof Controls\TextBase || $control instanceof Controls\SelectBox || $control instanceof Controls\MultiSelectBox) {
				$control->getControlPrototype()->addClass('form-control');
			} elseif ($control instanceof Controls\Checkbox || $control instanceof Controls\CheckboxList || $control instanceof Controls\RadioList) {
				$control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
			}
		}
	}


}
