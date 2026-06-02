<?php
namespace Devture\Component\DBAL\Repository;

interface RepositoryInterface {

	/**
	 * Creates a model object from the data object.
	 *
	 * @param array $data
	 * @return object
	 */
	public function createModel(array $data);

	public function add($entity);

	public function update($entity);

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
