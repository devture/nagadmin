<?php
namespace Devture\Component\DBAL\Repository;

interface RepositoryInterface {

	/**
	 * Creates a model object from the data object.
	 *
	 * @param array<string, mixed> $data
	 * @return object
	 */
	public function createModel(array $data);

	/**
	 * @param object $entity
	 * @return void
	 */
	public function add($entity);

	/**
	 * @param object $entity
	 * @return void
	 */
	public function update($entity);

	/**
	 * @param object $entity
	 * @return void
	 */
	public function delete($entity);

	/**
	 * @param mixed $id
	 * @throws \Devture\Component\DBAL\Exception\NotFound
	 * @return object
	 */
	public function find($id);

	/**
	 * @return object[]
	 */
	public function findAll();

}
