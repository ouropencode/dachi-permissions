<?php
namespace Dachi\Permissions;

use Dachi\Core\Database;
use Dachi\Core\Request;
use Dachi\Core\Template;
use Dachi\Core\Configuration;

/**
 * The Permissions class is responsable for providing an interface to user permissions
 *
 * @version   2.0.0
 * @since     2.0.0
 * @license   LICENCE.md
 * @author    LemonDigits.com <devteam@lemondigits.com>
 */
class Permissions {
	protected static $active_user_permissions = null;

	const LEVEL_DEFAULT   = 0;
	const LEVEL_UNSAFE    = 1;
	const LEVEL_DANGEROUS = 2;
	const LEVEL_HIDDEN    = 3;

	public static function load() {
		if(is_array(self::$active_user_permissions))
			return false;

		self::$active_user_permissions = array();

		if(Request::getSession("dachi_authenticated", false) == false)
			return;

		$active_user = Database::getRepository('Authentication:ModelUser')->findOneBy(array(
			"id" => Request::getSession("dachi_authenticated", false)
		));
		if(!$active_user) return false;

		$role = $active_user->getRole();
		if(!$role) return false;

		foreach($role->getPermissions() as $perm)
			self::$active_user_permissions[$perm->getBit()] = true;

		$output_user = array(
			"id"         => $active_user->getId(),
			"first_name" => $active_user->getFirstName(),
			"last_name"  => $active_user->getLastName(),
			"email"      => $active_user->getEmail(),
			"role"       => array(
				"id"   => $role->getId(),
				"name" => $role->getName()
			)
		);

		if(Configuration::get("authentication.identifier", "email") == "username")
			$output_user["username"] = $active_user->getUsername();

		Request::setData("active_user", $output_user);
		Request::setData("active_user_id", $active_user->getId());
		Request::setData("dachi_permissions", self::$active_user_permissions);

		return true;
	}

	public static function getActiveUser() {
		if(Request::getSession("dachi_authenticated", false) == false)
			return false;

		return Database::getRepository('Authentication:ModelUser')->findOneBy(array(
			"id" => Request::getSession("dachi_authenticated", false)
		));
	}

	public static function has($bit) {
		if(!is_array(self::$active_user_permissions))
			self::load();

		if(isset(self::$active_user_permissions[$bit]) && self::$active_user_permissions[$bit] === true)
			return true;

		return false;
	}

	public static function hasUser($bit, $user) {
		$permissions = array();
		foreach($user->getRole()->getPermissions() as $perm)
			$permissions[$perm->getBit()] = true;

		return isset($permissions[$bit]) && $permissions[$bit] == true;
	}

	public static function enforce($bit) {
		if(!is_array(self::$active_user_permissions))
			self::load();

		if(isset(self::$active_user_permissions[$bit]) && self::$active_user_permissions[$bit] === true)
			return true;

		return self::fail($bit);
	}

	public static function enforceUser($bit, $user) {
		$permissions = array();
		foreach($user->getRole()->getPermissions() as $perm)
			$permissions[$perm->getBit()] = true;

		if(isset($permissions[$bit]) && $permissions[$bit] == true)
			return true;

		return self::fail($bit);
	}

	public static function fail($bit = "unknown") {
		Request::setResponseCode("error", "Insufficient Permission");
		Request::setData("failed-permission-bit", $bit);

		if(Request::isAPI())
			return false;

		if(Request::isAjax()) {
			Template::redirect("/");
			return false;
		}

		if(Configuration::get("authentication.redirect-on-fail", true)) {
			$destination = Configuration::get("authentication.redirect-to", "/auth");

			if(!self::getActiveUser())
				$destination = "/auth";

			Template::redirect($destination);
		}

		return false;
	}

	public static function register($bit, $name = null, $description = null, $display_path = array(), $safety_level = Permissions::LEVEL_HIDDEN) {
		$permission = Database::getRepository('Authentication:ModelPermission')->findOneByBit($bit);
		if($permission == null) {
			$permission = new Authentication\ModelPermission();
			Database::persist($permission);
		}

		$permission->setBit($bit);
		$permission->setName($name == null ? $bit : $name);
		$permission->setDescription($description == null ? $bit : $description);
		$permission->setDisplayPath($display_path);
		$permission->setSafetyLevel($safety_level);

		Database::flush();
	}

}
