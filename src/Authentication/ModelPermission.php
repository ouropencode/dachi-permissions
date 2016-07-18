<?php
namespace Dachi\Permissions\Authentication;

use Dachi\Core\Model;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="authentication_permissions")
 */
class ModelPermission extends Model {

	/**
	 * @Id @Column(type="string")
	 */
	protected $bit;

	/**
	 * @Column(type="string")
	 */
	protected $name;

	/**
	 * @Column(type="string")
	 */
	protected $description;

	public function getBit() {
		return $this->bit;
	}

	public function setBit($bit) {
		$this->bit = $bit;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getDescription() {
		return $this->description;
	}

	public function setDescription($description) {
		$this->description = $description;
	}

}