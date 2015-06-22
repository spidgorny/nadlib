<?php

class SQLNow extends AsIs {

    function __construct() {
        parent::__construct('');
    }

    function __toString() {
        $map = array(
            'sqlite' => "datetime('now')",
            'mysql' => 'now()',
            'ms' => 'GetDate()',
            'pg' => 'CURRENT_TIMESTAMP',
        );
        $schema = $this->db->getScheme();
        $content = $map[$schema] ?: end($map);
        return $content;    // should not be quoted
    }

}
