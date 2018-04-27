<?php
namespace Dachi\Permissions\Authentication;

use Dachi\Core\Model;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
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
	 * @Column(type="text")
	 */
	protected $description;

	/**
	 * @Column(type="text")
	 */
	protected $display_path;

	/**
	 * @Column(type="integer")
	 */
	protected $safety_level;

	/**
	 * @Column(type="integer")
	 */
	protected $priority;

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

	public function getDisplayPath() {
		return json_decode($this->display_path);
	}

	public function setDisplayPath($display_path) {
		$this->display_path = json_encode($display_path);
	}

	public function getSafetyLevel() {
		return $this->safety_level;
	}

	public function setSafetyLevel($safety_level) {
		$this->safety_level = $safety_level;
	}

	public function getPriority() {
		return $this->priority;
	}

	public function setPriority($priority) {
		$this->priority = $priority;
	}

}
