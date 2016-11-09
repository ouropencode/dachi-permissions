<?php
namespace Dachi\Permissions\Authentication\Models;

use Dachi\Core\Database;
use Dachi\Core\Configuration;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository {

	public function getDataTable($table) {
		$mapping = array(
			"id"         => "u.id",
			"username"   => "u.username",
			"first_name" => "u.first_name",
			"last_name"  => "u.last_name",
			"email"      => "u.email",
			"created"    => "u.created",
			"last_login" => "u.last_login",
			"role"       => "u.role"
		);

		$selectQuery = Database::createQueryBuilder()
			->select('u')
			->from('Authentication:User', 'u')
			->setFirstResult($table->getStartResult())
			->setMaxResults($table->getMaxResults());

		$orderBy = $table->getOrderBy($mapping);
		if($orderBy)
			$selectQuery = $selectQuery->add('orderBy', $orderBy);

		$countQuery = Database::createQueryBuilder()
			->select('COUNT(u.id)')
			->from('Authentication:User', 'u');

		$where = $table->getWhere($mapping);
		foreach($where as $filter) {
			$selectQuery = $filter->applyTo($selectQuery);
			$countQuery  = $filter->applyTo($countQuery);
		}

		$records = array();
		foreach($selectQuery->getQuery()->getResult() as $d) {
			$records[] = array(
				"id"         => $d->getId(),
				"username"   => $d->getUsername(),
				"first_name" => $d->getFirstName(),
				"last_name"  => $d->getLastName(),
				"email"      => $d->getEmail(),
				"created"    => $d->getCreated(),
				"last_login" => $d->getLastLogin(),
				"role"       => $d->getRole()
			);
		}

		$total_filtered = $countQuery->getQuery()->getSingleScalarResult();
		$total = Database::createQuery("SELECT COUNT(u.id) FROM Authentication:User u")->getSingleScalarResult();

		return array(
			"total"          => $total,
			"total_filtered" => $total_filtered,
			"records"        => $records
		);
	}

}
