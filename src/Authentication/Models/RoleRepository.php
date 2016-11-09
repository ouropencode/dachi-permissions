<?php
namespace Dachi\Permissions\Authentication\Models;

use Dachi\Core\Database;

use Doctrine\ORM\EntityRepository;

class RoleRepository extends EntityRepository {

	public function getDataTable($table) {
		$mapping = array(
			"id"          => "r.id",
			"name"        => "r.name",
			"created"     => "r.created",
			"permissions" => "r.permissions"
		);

		$selectQuery = Database::createQueryBuilder()
			->select('r')
			->from('Authentication:Role', 'r')
			->setFirstResult($table->getStartResult())
			->setMaxResults($table->getMaxResults());

		$orderBy = $table->getOrderBy($mapping);
		if($orderBy)
			$selectQuery = $selectQuery->add('orderBy', $orderBy);

		$countQuery = Database::createQueryBuilder()
			->select('COUNT(r.id)')
			->from('Authentication:Role', 'r');

		$where = $table->getWhere($mapping);
		foreach($where as $filter) {
			$selectQuery = $filter->applyTo($selectQuery);
			$countQuery  = $filter->applyTo($countQuery);
		}

		$records = array();
		foreach($selectQuery->getQuery()->getResult() as $d) {
			if($d->getId() == 1 && !\Dachi\Permissions\Permissions::has("global.root-user"))
				continue;

			$records[] = array(
				"id"          => $d->getId(),
				"name"        => $d->getName(),
				"created"     => $d->getCreated(),
				"permissions" => $d->getPermissions()
			);
		}

		$total_filtered = $countQuery->getQuery()->getSingleScalarResult();
		$total = Database::createQuery("SELECT COUNT(r.id) FROM Authentication:Role r")->getSingleScalarResult();

		return array(
			"total"          => $total,
			"total_filtered" => $total_filtered,
			"records"        => $records
		);
	}

}
