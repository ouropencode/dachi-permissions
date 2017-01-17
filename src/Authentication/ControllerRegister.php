<?php
namespace Dachi\Permissions\Authentication;

use Dachi\Core\Controller;
use Dachi\Core\Request;
use Dachi\Core\Template;
use Dachi\Core\Database;
use Dachi\Core\Configuration;

use Dachi\Permissions\Permissions;

/**
 * The ControllerRegister class is responsable for new user registration
 *
 * This Controller provides routes for:
 *
 *     /auth/register
 *     /auth/register/create
 *
 * @version   2.0.0
 * @since     2.0.0
 * @license   LICENCE.md
 * @author    LemonDigits.com <devteam@lemondigits.com>
 */
class ControllerRegister extends Controller {

	private function handle_redirect_uris() {
		$dachi_redirect_uri = Request::getArgument("dachi_redirect_uri", false);
		if($dachi_redirect_uri !== false)
			Request::setSession("dachi_redirect_uri", $dachi_redirect_uri);

		$sso_redirect_uri = Request::getArgument("sso_redirect_uri", false);
		if($sso_redirect_uri !== false)
			Request::setSession("sso_redirect_uri", $sso_redirect_uri);
	}

	/**
	 * @route-url /auth/register
	 */
	public function auth_register() {
		$this->handle_redirect_uris();
		Request::setSession("dachi_authenticated", false);

		if(Permissions::getActiveUser())
			return Template::redirect("/auth/login/check");

		if(Configuration::get("authentication.register-enabled", true) == false)
			return Template::redirect("/auth");

		Request::setData("auth_id", Configuration::get("authentication.identifier", "email"));
		Request::setData("mailing_list", Configuration::get("authentication.mailinglist-enabled", true));
		Template::display("@Authentication/register", "page_content");
	}

	/**
	 * @route-url /auth/register/create
	 * @route-render /auth/login
	 */
	public function auth_register_create() {
		if(Configuration::get("authentication.register-enabled", true) == false)
			return Template::redirect("/auth");

		$existing = Database::getRepository('Authentication:ModelUser')->findOneBy(array(
			"email" => Request::getArgument("email")
		));

		if($existing)
			return Request::setResponseCode("error", "E-Mail address is already in use");

		if(Configuration::get("authentication.identifier", "email") == "username" || Configuration::get("authentication.identifier", "email") == "password") {
			$existing = Database::getRepository('Authentication:ModelUser')->findOneBy(array(
				"username" => Request::getArgument("username")
			));

			if($existing)
				return Request::setResponseCode("error", "Username is already in use");
		}

		if(Request::getArgument("password") !== Request::getArgument("cpassword"))
			return Request::setResponseCode("error", "Passwords do not match");

		$user = new ModelUser();
		$user->setRole(Database::getReference("Authentication:ModelRole", 1));
		$user->setFirstName(Request::getArgument("first_name"));
		$user->setLastName(Request::getArgument("last_name"));

		$identifier = Configuration::get("authentication.identifier", "email");
		if($identifier == "email") {
			$user->setEmail(Request::getArgument("email"));
			$user->setUsername("");
		} else if($identifier == "username") {
			$user->setEmail(Request::getArgument("email"));
			$user->setUsername(Request::getArgument("username"));
		} else if($identifier == "both") {
			$user->setEmail(Request::getArgument("email"));
			$user->setUsername(Request::getArgument("username"));
		} else {
			throw new \Exception("Invalid authentication identifier set in config.");
		}

		$user->setPassword(password_hash(Request::getArgument("password"), PASSWORD_BCRYPT));
		$user->setCreated(new \DateTime());
		$user->setLastLogin(new \DateTime());
		$user->setResetKey(null);
		Database::persist($user);
		Database::flush();

		if($user->getEmail()) {
			\Dachi\Helpers\EMail::send(array(
				"email"   => $user->getEmail(),
				"name"    => $user->getFirstName() . " " . $user->getLastName(),
				"subject" => "Account registration",
				"lead"    => "Welcome!",
				"content" => sprintf(
				                 "Your account for %s has been created. <a href=\"http://%s%s/auth/login\">click to login</a>",
				                 Configuration::get("dachi.siteName"),
				                 Configuration::get("dachi.domain"),
				                 Configuration::get("dachi.baseURL")
				             )
			));
		}

		Request::setSession("dachi_authenticated", $user->getId());
		$this->perform_redirect();

		Request::setResponseCode("success", "Account created successfully");
	}

	private function perform_redirect() {
		$redirect = "/";
		$redirect = Request::getSession("dachi_redirect_uri", $redirect);
		$redirect = Request::getArgument("dachi_redirect_uri", $redirect);
		Template::redirect($redirect);
	}
}
