<?php

/**
 * Description of LazyContainer
 *
 * @author Jiří Doušek <405245@mail.mini.cz>
 */
class LazyContainer extends Nette\Object {
	
	private $watchRepositoryFactory;
       
	private $personDbMapperFactory;

	public function __construct(){
			$this->watchRepositoryFactory = factory(function(){
					return new WatchRepository($this->personDbMapperFactory);
			});
			$this->personDbMapperFactory = factory(function(){
					return new PersonDbMapper($this->watchRepositoryFactory);
			});
	}
	
	public function factory($create){
       $created = false;
       $instance = null;
       return function () use (&$created, &$instance){
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
}
