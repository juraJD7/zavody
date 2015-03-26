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
		
		$this->template->token = $this->skautIS->getUser()->getLoginId();
	}
	
	private function login($post) {
		$this->skautIS->setLoginData($post);
		$this->user->login($this->skautIS->user->UserDetail());
		$this->user->setExpiration('30 minutes', TRUE);
		if ($this->user->isLoggedIn()) {
			$user = $this->userManager->load($this->user->id);			
		}
	}


	public function actionLogout() {
		$this->user->logout(TRUE);
		$url = $this->skautIS->getLogoutUrl();
		$this->skautIS->getUser()->resetLoginData();
		$this->redirectUrl($url);
	}

}
