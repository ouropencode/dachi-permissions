<?php
namespace Dachi\Permissions\Authentication;

use Dachi\Core\Controller;
use Dachi\Core\Configuration;
use Dachi\Core\Request;
use Dachi\Core\Template;
use Dachi\Core\Database;

/**
 * The ControllerPasswordReset class is responsable for managing password resets
 *
 * This Controller provides routes for:
 *
 *     /auth/reset-password
 *     /auth/reset-password/send
 *     /auth/reset-password/:id
 *     /auth/reset-password/:id/save
 *
 * @version   2.0.0
 * @since     2.0.0
 * @license   LICENCE.md
 * @author    LemonDigits.com <devteam@lemondigits.com>
 */
class ControllerPasswordReset extends Controller {

	/**
	 * @route-url /auth/reset-password
	 * @session
	 */
	public function auth_reset_password() {
		Request::setSession("dachi_authenticated", false);
		Request::setData("auth_id", Configuration::get("authentication.identifier", "email"));
		Template::display("@Authentication/reset-password", "page_content");
	}

	/**
	 * @route-url /auth/reset-password/send
	 * @route-render /auth/login
	 * @session
	 */
	public function auth_reset_password_send() {
		$user = Database::getRepository('Authentication:ModelUser')->findOneBy(array(
			"email" => Request::getArgument("email")
		));

		if($user && $user->getEmail()) {
			$key = hash('sha256', mt_rand() * time());
			$user->setResetKey(password_hash($key, PASSWORD_BCRYPT));
			Database::flush();

			\Dachi\Helpers\EMail::send(array(
				"email"   => $user->getEmail(),
				"name"    => $user->getFirstName() . " " . $user->getLastName(),
				"subject" => "Password reset request",
				"lead"    => "Reset your password!",
				"content" => "You have requested a password reset. If you did not request a reset, please ignore this email.<br /><br />" .
				             sprintf(
				                 "<a href=\"http://%s%s/auth/reset-password/%s\">click to reset your password</a>.",
				                 Configuration::get("dachi.domain"),
				                 Configuration::get("dachi.baseURL"),
				                 $key
				             )
			));
		}

		Request::setData("auth_id", Configuration::get("authentication.identifier", "email"));
		Request::setData("register_enabled", Configuration::get("authentication.register-enabled", true));
		Template::display("@Authentication/login", "page_content");
		Request::setResponseCode("success", "Reset link has been sent via email");
	}

	/**
	 * @route-url /auth/reset-password/:id
	 * @session
	 */
	public function auth_reset_password_claim() {
		Request::setSession("dachi_authenticated", false);
		Request::setData("reset_key", Request::getUri("id", "[0-9a-fA-F]+"));
		Request::setData("email", Request::getArgument("email", ""));
		Template::display("@Authentication/reset-password-new-password", "page_content");
	}

	/**
	 * @route-url /auth/reset-password/:id/save
	 * @session
	 */
	public function auth_reset_password_claim_save() {
		$user = Database::getRepository('Authentication:ModelUser')->findOneBy(array(
			"email" => Request::getArgument("email")
		));

		$reset_key = Request::getUri("id", "[0-9a-fA-F]+");
		if(!$user || !$reset_key || !password_verify($reset_key, $user->getResetKey()))
			return Request::setResponseCode("error", "Invalid email or reset key provided");

		$user->setPassword(password_hash(Request::getArgument("password"), PASSWORD_BCRYPT));
		$user->setResetKey(null);
		Database::flush();

		Request::setSession("dachi_authenticated", $user->getId());
		Request::setResponseCode("success", "Password updated");
		ControllerLogin::perform_redirect();
	}

}
