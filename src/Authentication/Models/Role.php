<?php
namespace Dachi\Permissions\Authentication\Models;

use Dachi\Core\Model;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity(repositoryClass="RoleRepository")
 * @Table(name="authentication_roles")
 */
class Role extends Model {
	/**
	 * @Id @Column(type="integer") @GeneratedValue
	 */
	protected $id;

	/**
	 * @Column(type="string")
	 */
	protected $name;

	/**
	 * @Column(type="string")
	 */
	protected $entry_point;

	/**
	 * @Column(type="datetime")
	 */
	protected $created;

	/**
	 * @ManyToMany(targetEntity="Permission", fetch="EAGER")
	 * @JoinTable(
	 *     name               = "authentication_roles_permissions",
	 *     joinColumns        = {@JoinColumn(name="role_id", referencedColumnName="id")},
	 *     inverseJoinColumns = {@JoinColumn(name="permission_bit", referencedColumnName="bit")}
	 * )
	 */
	private $permissions;

	public function __construct() {
		$this->permissions = new ArrayCollection();
	}

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getEntryPoint() {
		return $this->entry_point;
	}

	public function setEntryPoint($value) {
		$this->entry_point = $value;
	}

	public function getCreated() {
		return $this->created;
	}

	public function setCreated($datetime) {
		$this->created = $datetime;
	}

	public function addPermission($permission) {
		$this->permissions[] = $permission;
	}

	public function getPermissions() {
		return $this->permissions;
	}

}
