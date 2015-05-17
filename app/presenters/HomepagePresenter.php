<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{

	public function renderDefault()
	{
		$post = $this->request->post;		
		if ($post) {
			//není platný token - pouze přesměrujeme na hl. stránku
			if (!isset($post['skautIS_Token'])) {				
				$this->redirect('Homepage:');
			} else {
				//v post datech je skautIS_Token z úspěšného přihlášení. Provede se přihlášení i 
				//do aplikace a poté se přesměruje na případnou adresu z backlinku při volání přihlašování
				$this->login($post);
				if (isset($_GET['ReturnUrl'])) {
					$this->redirectUrl($_GET['ReturnUrl']);
				}
			}	
		}
	}
	
	/**
	 * Uloží data ze skautISu pomocí knihovny a provede přihlášení do aplikace (dojde k získání identity)
	 * 
	 * @param array $post $_POST
	 */
	private function login($post) {
		$this->skautIS->setLoginData($post);
		$userDetail = $this->skautIS->user->UserDetail();		
		$this->user->login($userDetail);
		$this->user->setExpiration('30 minutes', TRUE);		
		$this->userRepository->getUser($userDetail->ID); // aktualizuje udaje o uživateli
	}

	/**
	 * Odhlásí uživatele z aplikace, vymaže session a poté provede ohlášení ze skautISu
	 */
	public function actionLogout() {
		$this->user->logout(TRUE);
		$url = $this->skautIS->getLogoutUrl();
		$this->skautIS->getUser()->resetLoginData();
		$this->getSession("watch")->remove();
		$this->redirectUrl($url);
	}

}
