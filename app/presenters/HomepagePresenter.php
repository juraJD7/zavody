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
			if (!isset($post['skautIS_Token'])) {				
				$this->redirect('Homepage:');
			} else {
				$this->login($post);
				if (isset($_GET['ReturnUrl'])) {
					$this->redirectUrl($_GET['ReturnUrl']);
				}
			}	
		}
	}
	
	private function login($post) {
		$this->skautIS->setLoginData($post);
		$userDetail = $this->skautIS->user->UserDetail();		
		$this->user->login($userDetail);
		$this->user->setExpiration('30 minutes', TRUE);		
		$this->userRepository->getUser($userDetail->ID); // aktualizuje udaje o uživateli
	}


	public function actionLogout() {
		$this->user->logout(TRUE);
		$url = $this->skautIS->getLogoutUrl();
		$this->skautIS->getUser()->resetLoginData();
		$this->getSession("watch")->remove();
		$this->redirectUrl($url);
	}

}
