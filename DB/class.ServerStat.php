<?php

class ServerStat {
	var $start_time;
	var $LOG = array();
	var $COUNTQUERIES = 0;

	function __construct($start_time = NULL, $LOG = array(), $COUNTQUERIES = 0) {
		$this->start_time = $start_time;
		$this->LOG = $LOG;
		$this->COUNTQUERIES = $COUNTQUERIES;
	}

	function render($first = TRUE) {
		$s = new slTable($this->getPHPInfo(), 'width="100%"');
		$s->thes(array(
			'param' => '',
			'value' => array('no_hsc' => TRUE),
		));
		$content .= '<fieldset><legend>PHP Info</legend>'.$s->getContent().'</fieldset>';

		/*$s = new slTable($this->getPerformanceInfo(), 'width="100%"');
		$s->thes(array(
			'param' => '',
			'value' => array('no_hsc' => TRUE),
		));
		$content .= '<fieldset><legend>Performance</legend>'.$s->getContent().'</fieldset>';
		*/

		$s = new slTable($this->getServerInfo(), 'width="100%"');
		$s->thes(array(
			'param' => '',
			'value' => array('no_hsc' => TRUE),
		));
		$content .= '<fieldset><legend>Server Info</legend><div id="div_SystemInfo">'.$s.'</div>
		<!--input type="checkbox" onclick="reloadServerInfo(this);" id="input_reload"> <label for="input_reload">Reload</label-->
		</fieldset>';

		//if ($GLOBALS['prof']) $content .= $GLOBALS['prof']->printTimers(1);

/*		$s = new slTable('dumpQueries', 'width="100%"');
		$s->thes(array(
			'query' => array('name' => 'Query', 'no_hsc' => TRUE, 'colspan' => 7, 'new_tr' => TRUE),
			'function' => '<a href="javascript: void(0);" onclick="toggleRows(\'dumpQueries\');">Func.</a>',
			'line' => '(l)',
			//'results' => 'Rows',
			'elapsed' => array('name' => '1st', 'decimals' => 3),
			'count' => '#',
			'total' => array('name' => $totalTime, 'decimals' => 3),
			'percent' => '100%',
		));
		$s->data = $this->LOG;
		$s->isOddEven = TRUE;
		$s->more = 'class="nospacing"';
//		$content .= $s->getContent();
*/
		return $content;
	}

	function getPHPInfo() {
		$useMem = memory_get_usage();
		$allMem = intval(ini_get('memory_limit'))*1024*1024;

		$conf = array();
		$conf[] = array('param' => 'Server',			'value' => $_SERVER['SERVER_NAME']);
		$conf[] = array('param' => 'PHP',				'value' => phpversion());
		$conf[] = array('param' => 'Server time',		'value' => date('H:i:s'));
		$conf[] = array('param' => 'memory_limit',		'value' => number_format($allMem/1024, 3, '.', '').' Kb');
		$conf[] = array('param' => 'Mem. used',			'value' => number_format($useMem/1024, 3, '.', '').' Kb');
		//$conf[] = array('param' => 'Mem. used',			'value' => number_format($useMem / $allMem * 100, 3) . '%');
		//$conf[] = array('param' => 'Mem. used',			'value' => $this->getBar($useMem / $allMem * 100));
		$conf[] = array('param' => 'Mem. used %',			'value' => new HTMLTag('td', array(
			'style' => 'width: 100px; background: no-repeat url('.$this->getBarURL($useMem / $allMem * 100).');'
			), number_format($useMem / $allMem * 100, 3) . '%'));
		$conf[] = array('param' => 'Mem. peak',			'value' => number_format(memory_get_usage()/1024, 3, '.', '').' Kb');
        $sessionPath = ini_get('session.save_path');
		$sessionPath = $sessionPath ? $sessionPath : '/tmp';
		$sessionFile = $sessionPath.'/sess_'.session_id();
		//$conf[] = array('param' => 'Session File',		'value' => $sessionFile);
		$conf[] = array('param' => 'Sess. size',		'value' => @filesize($sessionFile));
		return $conf;
	}

	function getPerformanceInfo() {
		$this->LOG = is_array($this->LOG) ? $this->LOG : array();

		// calculating total sql time
		foreach ($this->LOG as $i => $row) {
			$totalTime += $row['total'];
		}
		$totalTime = number_format($totalTime, 3);

		// reformatting the data for output
		foreach ($this->LOG as $i => $row) {
			$this->LOG[$i]['percent'] = number_format($row['total'] * 100 / $totalTime, 3).'%';
			$this->LOG[$i]['query'] = '<span title="'.$this->LOG[$i]['title'].'">'.$this->LOG[$i]['query'].'</span>';
			$this->LOG[$i]['###TD_CLASS###'] = 'invisible';
		}
		usort($this->LOG, array($this, 'sortLog'));

		$allTime = array_sum(explode(' ', microtime())) - $this->start_time;
		$sqlTime = $totalTime;
		$phpTime = $allTime - $sqlTime;

		$conf = array();
		$conf[] = array('param' => '100% Time',			'value' => number_format($allTime, 3, '.', ''));
		$conf[] = array('param' => 'PHP Time',			'value' => number_format($phpTime, 3, '.', ''));
		$conf[] = array('param' => 'PHP Time %',		'value' => round($phpTime / $allTime * 100, 3).'%');
		$conf[] = array('param' => 'PHP Time %',		'value' => $this->getBar($phpTime / $allTime * 100));
		$conf[] = array('param' => 'SQL Time',			'value' => number_format($sqlTime, 3, '.', ''));
		$conf[] = array('param' => 'SQL Time %',		'value' => round($sqlTime / $allTime * 100, 3).'%');
		$conf[] = array('param' => 'SQL Time %',		'value' => $this->getBar($sqlTime / $allTime * 100));
		$conf[] = array('param' => 'Unique Q',			'value' => sizeof($this->LOG).'/'.$this->COUNTQUERIES);
		//$conf[] = array('param' => 'All queries',		'value' => $this->COUNTQUERIES);
		$conf[] = array('param' => 'Unique Q',			'value' => $this->getBar(sizeof($this->LOG) / $this->COUNTQUERIES * 100));
		return $conf;
	}

	function getServerInfo() {
		$conf = array();
		$diskpercent = (disk_total_space('/')-disk_free_space('/')) / disk_total_space('/') * 100;
		$conf[] = array('param' => 'Disk space',		'value' => number_format(disk_total_space('/')/1024/1024, 3, '.', '').' Mb');
		$conf[] = array('param' => 'Disk used',			'value' => number_format((disk_total_space('/')-disk_free_space('/'))/1024/1024, 3, '.', '').' Mb');
		//$conf[] = array('param' => 'Disk used',			'value' => number_format($diskpercent, 3, '.', '').' %');
		//$conf[] = array('param' => 'Disk used',			'value' => $this->getBar($diskpercent));
		$conf[] = array('param' => 'Disk used %',			'value' => new HTMLTag('td', array(
			'style' => 'width: 100px; background: no-repeat url('.$this->getBarURL($diskpercent).');'
			), number_format($diskpercent, 3) . '%'));
		$cpu = $this->getCpuUsage();
		//$conf[] = array('param' => 'CPU used',			'value' => number_format(100 - $cpu['idle'], 3, '.', '').'%');
		//$conf[] = array('param' => 'CPU used',			'value' => $this->getBar(100 - $cpu['idle']));
		$conf[] = array('param' => 'CPU used %',			'value' => new HTMLTag('td', array(
			'style' => 'width: 100px; background: no-repeat url('.$this->getBarURL(100 - $cpu['idle']).');'
			), number_format(100 - $cpu['idle'], 3) . '%'));
		$ram = $this->getRAMInfo();
		$conf[] = array('param' => 'RAM',				'value' => number_format($ram['total']/1024, 3, '.', '').' Mb');
		$conf[] = array('param' => 'RAM used',			'value' => number_format($ram['used']/1024, 3, '.', '').' Mb');
		//$conf[] = array('param' => 'RAM used',			'value' => number_format($ram['percent'], 3, '.', '').'%');
		//$conf[] = array('param' => 'RAM used',			'value' => $this->getBar($ram['percent']));
		$conf[] = array('param' => 'RAM used %',			'value' => new HTMLTag('td', array(
			'style' => 'width: 100px; background: no-repeat url('.$this->getBarURL($ram['percent']).');'
			), number_format($ram['percent'], 3) . '%'));
		$conf[] = array('param' => 'Uptime',			'value' => $this->format_uptime(implode('', array_slice(explode(" ", @file_get_contents('/proc/uptime')), 0, 1))));
		$conf[] = array('param' => 'Server load',		'value' => implode('', array_slice(explode(' ', @file_get_contents('/proc/loadavg')), 0, 1)));

		return $conf;
	}

	function format_uptime($seconds) {
        $secs = intval($seconds % 60);
        $mins = intval($seconds / 60 % 60);
        $hours = intval($seconds / 3600 % 24);
        $days = intval($seconds / 86400);

        $uptimeString .= $days .  "D ";
        $uptimeString .= str_pad($hours, 2, '0', STR_PAD_LEFT) . ":";
        $uptimeString .= str_pad($mins, 2, '0', STR_PAD_LEFT) . ":";
        $uptimeString .= str_pad($secs, 2, '0', STR_PAD_LEFT);
        return $uptimeString;
    }

	function getRAMInfo() {
		$mem = file_get_contents("/proc/meminfo");
        if (preg_match('/MemTotal\:\s+(\d+) kB/', $mem, $matches))
        {
            $totalp = $matches[1];
        }
        unset($matches);
        if (preg_match('/MemFree\:\s+(\d+) kB/', $mem, $matches))
        {
            $freep = $matches[1];
        }
        $freiq = $freep;
        $insgesamtq = $totalp;
        $belegtq = $insgesamtq - $freiq;
        $prozent_belegtq = 100 * $belegtq / $insgesamtq;
        $res = array(
        	'total' => $totalp,
        	'used' => $belegtq,
        	'free' => $freiq,
        	'percent' => $prozent_belegtq,
        );
        return $res;
	}

	function getBarURL($percent) {
		$content .= 'nadlib/bar.php?rating='.$percent;
		return $content;
	}

	function getBar($percent) {
		$content .= '<img src="'.$this->getBarURL($percent).'" />';
		return $content;
	}

	function getStat($_statPath) {
        if (trim($_statPath) == '')
        {
            $_statPath = '/proc/stat';
        }

        $stat = @file_get_contents($_statPath);

        if (substr($stat, 0, 3) == 'cpu')
        {
            $parts = explode(" ", preg_replace("!cpu +!", "", $stat));
        }
        else
        {
            return false;
        }

        $return = array();
        $return['user'] = $parts[0];
        $return['nice'] = $parts[1];
        $return['system'] = $parts[2];
        $return['idle'] = $parts[3];
        return $return;
    }

    function getCpuUsage($_statPath = '/proc/stat') {
        $time1 = $this->getStat($_statPath) or die("getCpuUsage(): couldn't access STAT path or STAT file invalid\n");
        sleep(1);
        $time2 = $this->getStat($_statPath) or die("getCpuUsage(): couldn't access STAT path or STAT file invalid\n");

        $delta = array();

        foreach ($time1 as $k => $v)
        {
            $delta[$k] = $time2[$k] - $v;
        }

        $deltaTotal = array_sum($delta);
        $percentages = array();

        foreach ($delta as $k => $v)
        {
            $percentages[$k] = $v / $deltaTotal * 100;
        }
        return $percentages;
	}

	function sortLog($a, $b) {
		$a = $a['total'];
		$b = $b['total'];
		if ($a == $b) {
			$aa = $a['query'];
			$bb = $b['query'];
			if ($aa == $bb) {
				return 0;
			} else if ($aa < $bb) {
				return 1;
			} else {
				return -1;
			}
		} else if ($a < $b) {
			return 1;
		} else {
			return -1;
		}
	}

	function __toString() {
		return $this->render();
	}

}
