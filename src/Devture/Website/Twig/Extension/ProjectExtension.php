<?php
namespace Devture\Website\Twig\Extension;

class ProjectExtension extends \Twig_Extension {

	private $projectName;

	public function __construct($projectName) {
		$this->projectName = $projectName;
	}

	public function getName() {
		return 'project_extension';
	}

	public function getFunctions() {
		return array(
			'get_project_name' => new \Twig_Function_Method($this, 'getProjectName'),
		);
	}

	public function getProjectName() {
		return $this->projectName;
	}

}
