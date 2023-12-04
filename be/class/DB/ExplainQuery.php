<?php

class ExplainQuery extends AppControllerBE {

	var $query;

	var $explain;

	var $result;

	var $time;

	var $profiles;

	var $profile;

	function __construct() {
		parent::__construct();
		$this->query = $this->request->getString('query');
		if ($this->query) {
			$this->db->perform('FLUSH TABLES');
			$this->explain = $this->db->fetchAll('EXPLAIN '.$this->query);
			$this->db->perform('SET PROFILING = On');

			$p = new Profiler();
			try {
				$this->result = $this->db->fetchAll($this->query);
			} catch (Exception $e) {
				$this->result = '<div class="error alert alert-error alert-danger">'.$e->getMessage().'</div>';
			}
			$this->time = $p->elapsed();

			$this->profiles = $this->db->fetchAll('SHOW PROFILES');
			$this->profile = $this->db->fetchAll('SHOW PROFILE FOR QUERY 1');
			$this->db->perform('SET PROFILING = Off');
		}
	}

	function render() {
		$f = new HTMLFormTable();
		$f->defaultBR = true;
		$f->desc = [
			'query' => [
				'label' => 'Query',
				'type' => 'textarea',
				'more' => 'style = "width: 100%; height: 15em;"',
			],
			'submit' => [
				'type' => 'submit',
				'value' => 'Explain',
			]
		];
		$f->fill($_REQUEST);
		$f->showForm();
		$content = $f;

		$content .= new slTable($this->explain);
		$content .= new slTable($this->profiles);
		if (is_array($this->result)) {
			$content .= new slTable($this->result);
		} else {
			$content .= $this->result;
		}

		$content = $this->encloseInFieldset($this->title = 'Explain Query', $content);
		return $content;
	}

	function sidebar() {
		$content = new slTable($this->profile);
		$content = $this->encloseInFieldset('Time: '.$this->time, $content);
		return $content;
	}

}
