<?php

class SQLNow extends AsIs {

    function __construct() {
        parent::__construct('');
    }

    function __toString() {
        $map = array(
            'sqlite' => "date('now')",
            'mysql' => 'now()',
        );
        $schema = $this->db->getScheme();
        $content = $map[$schema] ?: end($map);
        return $content;
    }

}
