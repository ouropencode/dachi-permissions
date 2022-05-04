<?php
namespace Dachi\Permissions\Authentication;

use Dachi\Core\Controller;
use Dachi\Core\Kernel;
use Dachi\Core\Request;
use Dachi\Core\Template;
use Dachi\Core\Database;
use Dachi\Core\Configuration;

use Dachi\Permissions\Permissions;

/**
 * The ControllerLogin class is responsable for logging in and confirming authentication of users
 *
 * This Controller provides routes for:
 *
 *     /auth/
 *     /auth/login
 *     /auth/login/check
 *
 * @version   2.0.0
 * @since     2.0.0
 * @license   LICENCE.md
 * @author    LemonDigits.com <devteam@lemondigits.com>
 */
class ControllerLogin extends Controller {

	public function __setup() {
		Permissions::register("global.root-user",  "Is Root User",  "Enables functionality useful to developers and system administrators.", array("System Administration"), Permissions::LEVEL_HIDDEN);
	}

	private function handle_redirect_uris() {
		$dachi_redirect_uri = Request::getArgument("dachi_redirect_uri", false);
		if($dachi_redirect_uri !== false)
			Request::setSession("dachi_redirect_uri", $dachi_redirect_uri);

		$sso_redirect_uri = Request::getArgument("sso_redirect_uri", false);
		if($sso_redirect_uri !== false)
			Request::setSession("sso_redirect_uri", $sso_redirect_uri);
	}

	/**
	 * @route-url /auth/
	 * @session
	 */
	public function auth_index() {
		$this->handle_redirect_uris();
		return $this->auth_login();
	}

	/**
	 * @route-url /auth/logout
	 * @session
	 */
	public function auth_logout() {
		$this->handle_redirect_uris();
		Request::setSession("dachi_authenticated", false);

		session_destroy();

		Template::redirect("/auth");
	}

	/**
	 * @route-url /auth/login
	 * @session
	 */
	public function auth_login() {
		$this->handle_redirect_uris();
		Request::setSession("dachi_authenticated", false);

		Request::setData("auth_id", Configuration::get("authentication.identifier", "email"));
		Request::setData("register_enabled", Configuration::get("authentication.register-enabled", true));

		Template::display("@Authentication/login", "page_content");
	}

	/**
	 * @route-url /auth/login/check
	 * @route-render /auth/login
	 * @session
	 */
	public function auth_login_check() {
		$identifier = Configuration::get("authentication.identifier", "email");
		if($identifier == "email") {
			$user = Database::getRepository('Authentication:ModelUser')->findOneBy(array(
				"email" => Request::getArgument("email")
			));
		} else if($identifier == "username") {
			$user = Database::getRepository('Authentication:ModelUser')->findOneBy(array(
				"username" => Request::getArgument("username")
			));
		} else if($identifier == "both") {
			$user = Database::getRepository('Authentication:ModelUser')->findOneBy(array(
				"username" => Request::getArgument("identifier")
			));
			if($user == null) {
				$user = Database::getRepository('Authentication:ModelUser')->findOneBy(array(
					"email" => Request::getArgument("identifier")
				));
			}
		} else {
			throw new \Exception("Invalid authentication identifier set in config.");
		}

		if(Request::getSession("dachi_authenticated", false) !== false)
			return self::perform_redirect();

    if(!$user) {
      Request::setResponseCode("error", "Invalid credentials.");
      return;
    }

    if($user->getLockoutUntil() && $user->getLockoutUntil() > new \DateTime()) {
      Request::setData("login_lockout", true);
      Request::setResponseCode("error", "Account locked out.<br><small>Too many login attempts, try again later.</small>");
      return;
    }

    $user->setLockoutCount($user->getLockoutCount() + 1);

		if (password_verify(Request::getArgument("password"), $user->getPassword())) {
			Request::setSession("dachi_authenticated", $user->getId());

			$user->setLastLogin(new \DateTime());
      $user->setLockoutCount(0);
      $user->setLockoutUntil(null);
			Database::flush();

			self::perform_redirect();

			Request::setResponseCode("success", "Logged in successfully.");
		} else {
			if ($user->getLockoutCount() > 5) {
        $until = new \DateTime();
        $until->add(new \DateInterval(sprintf("PT%dM", $user->getLockoutCount())));
        $user->setLockoutUntil($until);
			}
			Request::setResponseCode("error", "Invalid credentials.");
		}

    Database::flush();
	}

	public static function perform_redirect() {
		$sso_redirect = null;
		$sso_redirect = Request::getSession("sso_redirect_uri", $sso_redirect);
		$sso_redirect = Request::getArgument("sso_redirect_uri", $sso_redirect);
		if($sso_redirect && is_array(Configuration::get("authentication.sso-redirect-handler", null))) {
			$handler = Configuration::get("authentication.sso-redirect-handler", array());
			if(isset($handler["class"])) {
				$controller = new $handler["class"]();
				if($controller->{$handler["method"]}($sso_redirect)) {
					Request::setSession("sso_redirect_uri", false);
					return true;
				}
			}
		}

		$entry_point = "";
		if(Permissions::getActiveUser() && Permissions::getActiveUser()->getRole())
			$entry_point = Permissions::getActiveUser()->getRole()->getEntryPoint();

		$redirect = Request::getSession("dachi_redirect_uri", "/");
		$redirect = Request::getArgument("dachi_redirect_uri", $redirect);
		$redirect = $entry_point ? $entry_point : $redirect;
		Request::setSession("dachi_redirect_uri", false);
		Template::redirect($redirect);
	}
}

class InvalidAttemptsLockoutException extends \Dachi\Core\Exception { }
