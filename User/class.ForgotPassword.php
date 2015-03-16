<?php

class ForgotPassword extends AppController {
	public $login;

	function render() {
		$content = '';
		$this->login = $this->request->getTrim('login');
		$step2 = $this->request->getBool('step2');
		if (!$step2) {
			$content .= new View('ForgotPassword.phtml', $this);
		} else { // step2
			$user = new UserManager();
			$user->findInDB(array('email' => $this->login));
			if ($user->id) {
				$newPassword = $this->getRandomPassword();
				$user->update(array('password' => md5($newPassword)));
				$mail = new View('mailForgotPassword.txt', $this);
				$mail->newPassword = $newPassword;
				mail($user->data['email'], 'New generated password for BBMM.', $mail, $this->config->mailFrom);
				$content .= '<div class="message">Check your mail. Your new password is sent to '.$user->data['email'].'.</div>
				<a href="">Login here</a>';
			} else {
				$content .= '<div class="error">No such e-mail found in the DB.</div>';
				$content .= new View('ForgotPassword.phtml', $this);
			}
		}
		return $content;
	}

	function getRandomPassword($length = 10) {
		$conso=array("b","c","d","f","g","h","j","k","l","m","n","p","r","s","t","v","w","x","y","z");
		$vocal=array("a","e","i","o","u");
		$password="";
		srand ((double)microtime()*1000000);
		for($i=1; $i <= $length/2; $i++) {
			$password .= $conso[rand(0,19)];
			$password .= $vocal[rand(0,4)];
			if (rand()/getrandmax() > 0.8) {
				$password .= ' ';
			}
		}
		return $password;
	}

}
