<?php

/**
 * Description of LazyContainer
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class LazyContainer extends Nette\Object {
	
	private $watchRepositoryFactory;
       
	private $personRepositoryFactory;
	
	private $personDbMapperFactory;
	
	private $raceRepositoryFactory;
	
	private $raceDbMapperFactory;
	
	private $watchDbMapperFactory;
	
	public function __construct(\Nette\Database\Context $database, UserRepository $userRepository, UnitRepository $unitRepository, PersonIsMapper $isMapper){
			$this->watchRepositoryFactory = $this->factory(function() use($unitRepository, $userRepository) {
					return new WatchRepository($this->raceRepositoryFactory, $this->personRepositoryFactory, $unitRepository, $userRepository, $this->watchDbMapperFactory);
			});
			$this->personRepositoryFactory = $this->factory(function()use ($isMapper){
					return new PersonRepository($isMapper, $this->personDbMapperFactory);
			});
			$this->personDbMapperFactory = $this->factory(function()use ($database, $userRepository, $unitRepository){
					return new PersonDbMapper($database, $userRepository, $unitRepository, $this->watchRepositoryFactory);
			});
			$this->raceRepositoryFactory = $this->factory(function() {
					return new RaceRepository($this->raceDbMapperFactory);
			});
			$this->raceDbMapperFactory = $this->factory(function()use ($database, $userRepository, $unitRepository){
					return new RaceDbMapper($database, $userRepository, $unitRepository, $this->watchRepositoryFactory);
			});
			$this->watchDbMapperFactory = $this->factory(function()use ($database, $userRepository, $unitRepository){
					return new WatchDbMapper($database, $userRepository, $unitRepository, $this->personRepositoryFactory);
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
	
	public function getPersonDbMapper() {
		return call_user_func($this->personDbMapperFactory);		
	}
	
	public function getRaceRepository() {
		return call_user_func($this->raceRepositoryFactory);		
	}
	
	public function getRaceDbMapper() {
		return call_user_func($this->raceDbMapperFactory);		
	}
	
	public function getWatchDbMapper() {
		return call_user_func($this->watchDbMapperFactory);		
	}
}
