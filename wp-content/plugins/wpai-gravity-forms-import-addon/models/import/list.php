<?php

class PMGI_Import_List extends PMGI_Model_List {
	public function __construct() {
		parent::__construct();
		$this->setTable(PMGI_Plugin::getInstance()->getTablePrefix() . 'imports');
	}
}