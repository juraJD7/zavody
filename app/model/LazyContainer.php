<?php

/**
 * Description of LazyContainer
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class LazyContainer extends Nette\Object {
	
	private $watchRepositoryFactory;
       
	private $personRepositoryFactory;

	public function __construct(RaceRepository $raceRepository, UnitRepository $unitRepository, WatchDbMapper $watchDbMapper, PersonIsMapper $isMapper, PersonDbMapper $personDbMapper){
			$this->watchRepositoryFactory = $this->factory(function() use($raceRepository, $unitRepository, $watchDbMapper) {
					return new WatchRepository($raceRepository, $this->personRepositoryFactory, $unitRepository, $watchDbMapper);
			});
			$this->personRepositoryFactory = $this->factory(function()use ($isMapper, $personDbMapper){
					return new PersonRepository($isMapper, $personDbMapper);
			});
	}
	
	private function factory($create){
       $created = false;
       $instance = null;
       return function () use (&$created, $create, &$instance){
               if(!$created){
                       $instance = $create();
                       $created = true;
               }
               return $instance;
		};
	}
	
	public function getWatchRepository(){
		return call_user_func($this->watchRepositoryFactory);
	}
	
	public function getPersonRepository() {
		return call_user_func($this->personRepositoryFactory);		
	}
}
