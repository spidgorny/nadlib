<?php
/********************************************************************************\
 * Copyright (C) Carl Taylor (cjtaylor@adepteo.com)                             *
 * Copyright (C) Torben Nehmer (torben@nehmer.net) for Code Cleanup             *
 *                                                                              *
 * This program is free software; you can redistribute it and/or                *
 * modify it under the terms of the GNU General Public License                  *
 * as published by the Free Software Foundation; either version 2               *
 * of the License, or (at your option) any later version.                       *
 *                                                                              *
 * This program is distributed in the hope that it will be useful,              *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of               *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                *
 * GNU General Public License for more details.                                 *
 *                                                                              *
 * You should have received a copy of the GNU General Public License            *
 * along with this program; if not, write to the Free Software                  *
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.  *
\********************************************************************************/

/// Enable multiple timers to aid profiling of performance over sections of code

class TaylorProfiler {
    var $description;
    var $description2;
    var $startTime;
    var $endTime;
    var $initTime;
    var $cur_timer;
    var $stack;
    var $trail;
    var $trace;
    var $count;
    var $running;
    var $output_enabled;
    var $trace_enabled;

	/**
	 * @var SplObjectStorage
	 */
	static $sos;

	static $instance;

	/**
	 * @var string "html" or "" for X-Tick header
	 */
	public $tickTo;

	/**
    * Initialise the timer. with the current micro time
    */
    function TaylorProfiler( $output_enabled=false, $trace_enabled=false) {
        $this->description = array();
        $this->startTime = array();
        $this->endTime = array();
        $this->cur_timer = "";
        $this->stack = array();
        $this->trail = "";
        $this->trace = array();
        $this->count = array();
        $this->running = array();
        $this->initTime = $this->getMicroTime();
        //$this->initTime = $_SERVER['REQUEST_TIME'];	// since PHP 5.4.0
		//debug($this->initTime);
        $this->output_enabled = $output_enabled;
        $this->trace_enabled = $trace_enabled;
        $this->startTimer('unprofiled');
    }

    // Public Methods

	function getName() {
		if (class_exists('MySQL') && method_exists('MySQL', 'getCaller')) {
			$name = MySQL::getCaller();
		} elseif (class_exists('Debug') && method_exists('Debug', 'getCaller')) {
			$name = Debug::getCaller();
		} else if (class_exists('dbLayerPG')) {
			$i = 3;
			$name = dbLayerPG::getCaller($i, 2);
		} else {
			$name = 'noname';
		}
		return $name;
	}

	/**
	 *   Start an individual timer
	 *   This will pause the running timer and place it on a stack.
	 * @param string $name name of the timer
	 * @param string $desc description of the timer
	 */
    function startTimer($name = NULL, $desc="" ){
		$name = $name ? $name : $this->getName();
    	if ($this->trace_enabled) {
	        $this->trace[] = array(
				'time' => time(),
				'function' => $name." {",
				'memory' => memory_get_usage()
		);
    	}
		if ($this->output_enabled) {
	        $n=array_push( $this->stack, $this->cur_timer );
	        $this->__suspendTimer( $this->stack[$n-1] );
	        $this->startTime[$name] = $this->getMicroTime();
	        $this->cur_timer=$name;
	        $this->description[$name] = $desc;
	        if (!array_key_exists($name,$this->count)) {
	            $this->count[$name] = 1;
	        } else {
	            $this->count[$name]++;
	        }
    	}
    }

    /**
    *   Stop an individual timer
    *   Restart the timer that was running before this one
    *   @param string $name name of the timer
    */
    function stopTimer($name = NULL) {
		$name = $name ? $name : $this->getName();
    	if ($this->trace_enabled) {
	        $this->trace[] = array('time' => time(), 'function' => "$name }", 'memory' => memory_get_usage());
    	}
    	if ($this->output_enabled) {
	        $this->endTime[$name] = $this->getMicroTime();
	        if (!array_key_exists($name, $this->running)) {
	            $this->running[$name] = $this->elapsedTime($name);
	        } else {
	            $this->running[$name] += $this->elapsedTime($name);
	        }
	        $this->cur_timer=array_pop($this->stack);
	        $this->__resumeTimer($this->cur_timer);
    	}
    }

    /**
    *   measure the elapsed time of a timer without stoping the timer if
    *   it is still running
    */
    function elapsedTime($name){
        // This shouldn't happen, but it does once.
        if (!array_key_exists($name,$this->startTime))
            return 0;

        if(array_key_exists($name,$this->endTime)){
            return ($this->endTime[$name] - $this->startTime[$name]);
        } else {
            $now=$this->getMicroTime();
            return ($now - $this->startTime[$name]);
        }
    }//end start_time

    /**
    *   Measure the elapsed time since the profile class was initialised
    *
    */
    function elapsedOverall(){
        $oaTime = $this->getMicroTime() - $this->initTime;
        return($oaTime);
    }//end start_time

    /**
    * Print out a log of all the timers that were registered
    */
    function printTimers($enabled=false) {
		if ($this->output_enabled||$enabled) {
			$this->stopTimer('unprofiled');
            $tot_perc = 0;
            ksort($this->description);
            $oaTime = $this->getMicroTime() - $this->initTime;

            $together = array();
            while (list ($key, $val) = each ($this->description)) {
            	$row = array();
            	$row['desc'] = $val;
                $row['time'] = $this->elapsedTime($key);
                $row['total'] = $this->running[$key];
                $row['count'] = $this->count[$key];
                $row['avg'] = $row['total']*1000/$row['count'];
                $row['perc'] = ($row['total']/$oaTime)*100;
                $together[$key] = $row;
	            if ($key == 'unprofiled') {
		            $together[$key]['bold'] = true;
		            $together[$key]['desc'] = 'Between new TaylorProfiler() and printTimers()';
	            }
            }

            // add missing
            $TimedTotal = 0;
            foreach ($together as $row) {
				$TimedTotal += $row['total'];
            }
            $missed=$oaTime-$TimedTotal;
            $perc = ($missed/$oaTime)*100;
            $tot_perc+=$perc;
            $together['Missed between the calls'] = array(
            	'desc' => 'Missed between the calls ('.$oaTime.'-'.$TimedTotal.'['.sizeof($together).'])',
	            'bold' => true,
	            'time' => number_format($missed, 2, '.', ''),
            	'total' => number_format($missed, 2, '.', ''),
            	'count' => 0,
            	'perc' => number_format($perc, 2, '.', '').'%',
            );

			if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
				$requestTime = $_SERVER['REQUEST_TIME_FLOAT']
					? $_SERVER['REQUEST_TIME_FLOAT']
					: $_SERVER['REQUEST_TIME'];
				$startup = $this->initTime - $requestTime;
				$together['Startup'] = array(
					'desc'  => 'Startup (REQUEST_TIME_FLOAT) ('.$this->initTime.'-'.$requestTime.')',
					'bold'  => true,
					'time'  => number_format($startup, 2, '.', ''),
					'total' => number_format($startup, 2, '.', ''),
					'count' => 0,
					'perc'  => number_format($startup / $oaTime * 100, 2, '.', '') . '%',
				);
			}

            uasort($together, array($this, 'sort'));

			$table = array();
			$i = 0;
			foreach ($together as $key => $row) {
			    $desc = $row['desc'];
	            $total = $row['total'];
                $TimedTotal += $total;
	            $perc = $row['perc'];
	            $tot_perc += $perc;
				$htmlKey = htmlspecialchars($key);
				if (ifsetor($row['bold'])) {
					$htmlKey = '<b>'.$htmlKey.'</b>';
				}
				$desc = $this->description2[$key] ? $this->description2[$key] : $desc;
	            $table[] = array(
	               	'nr' => ++$i,
	               	'count' => $row['count'],
	               	'time, ms' => number_format($total*1000, 2, '.', '').'',
	               	'avg/1' => number_format(ifsetor($row['avg']), 2, '.', '').'',
	               	'percent' => is_numeric($perc)
						? number_format($perc, 2, '.', '').'%'
						: $perc,
	                'routine' => '<span title="'.htmlspecialchars($desc).'">'.$htmlKey.'</span>',
	            );
		   }

            $s = new slTable($table, 'class="nospacing no-print" width="100%"');
            $s->thes(array(
            	'nr' => 'nr',
            	'count' => array(
					'name' => 'count',
					'align' => 'right'
				),
            	'time, ms' => array(
					'name' => 'time, ms',
					'align' => 'right'
				),
            	'avg/1' => array(
					'name' => 'avg/1',
					'align' => 'right'
				),
            	'percent' => array(
					'name' => 'percent',
					'align' => 'right'
				),
            	'routine' => array(
					'name' => 'routine',
					'no_hsc' => true,
					'wrap' => new Wrap('<small>|</small>'),
				),
            ));
			$s->isOddEven = true;
            $s->footer = array(
            	'nr' => 'total',
            	'time, ms' => number_format($oaTime*1000, 2, '.', ''),
            	'percent' => number_format($tot_perc, 2, '.', '').'%',
            	'routine' => "OVERALL TIME (".number_format(memory_get_peak_usage()/1024/1024, 3, '.', '')."MB)",
            );
            $out = Request::isCLI()
				? $s->getCLITable(true)
				: $s->getContent();
            return $out;
        }
		return NULL;
    }

    function sort($a, $b) {
    	$a = $a['perc'];
    	$b = $b['perc'];
    	if ($a > $b) return -1;
    	if ($a < $b) return +1;
    	if ($a == $b) return 0;
    }

    function printTrace( $enabled=false ) {
        if($this->trace_enabled||$enabled) {
        	$prev = 0;
        	$prevt = $this->trace[0]['time'];
        	foreach ($this->trace as $i => $trace) {
        		$this->trace[$i]['time'] = date('i:s', $trace['time'] - $prevt);
        		$this->trace[$i]['diff'] = number_format(($trace['memory'] - $prev)/1024, 1, '.', ' '). ' KB';
        		$this->trace[$i]['memory'] = number_format(($trace['memory'])/1024, 1, '.', ' '). ' KB';
        		$prev = $trace['memory'];
        	}
			return new slTable($this->trace);
        }
    }

	function analyzeTraceForLeak() {
		$func = array();
		foreach ($this->trace as $i => $trace) {
			$func[$trace['function']]++;
		}
		ksort($func);
		return slTable::showAssoc($func);
	}

    /// Internal Use Only Functions

    /**
    * Get the current time as accuratly as possible
    *
    */
    function getMicroTime() {
		return microtime(true);
    }

    /**
    * resume  an individual timer
    *
    */
    function __resumeTimer($name){
        $this->trace[] = array('time' => time(), 'function' => "$name {...", 'memory' => memory_get_usage());
        $this->startTime[$name] = $this->getMicroTime();
    }

    /**
    *   suspend  an individual timer
    *
    */
    function __suspendTimer($name){
        $this->trace[] = array('time' => time(), 'function' => "$name }...", 'memory' => memory_get_usage());
        $this->endTime[$name] = $this->getMicroTime();
        if (!array_key_exists($name, $this->running))
            $this->running[$name] = $this->elapsedTime($name);
        else
            $this->running[$name] += $this->elapsedTime($name);
    }

    function getMaxMemory() {
	    $ret = NULL;
    	$amem = array2::array_column($this->trace, 'memory');
    	if (sizeof($amem)) {
    		$ret = max($amem);
    	}
    	return $ret;
    }

	static function getMemoryUsage($returnString = false) {
		static $max;
		$max = $max ? $max : self::return_bytes(ini_get('memory_limit'));
		$cur = memory_get_usage(true);
		if ($returnString) {
			$content = str_pad(number_format($cur, 0, '.', ''), 4, ' ', STR_PAD_LEFT).'/'.$max.'MB '.number_format($cur/$max*100, 3, '.', '').'% ';
		} else {
			$content = number_format($cur/$max, 3, '.', '');
		}
		return $content;
	}

	static function addMemoryMap($obj) {
		self::$sos = self::$sos ? self::$sos : new SplObjectStorage();
		self::$sos->attach($obj);
	}

	static function getMemoryMap() {
		$table = array();
		foreach (self::$sos as $obj) {
			$class = get_class($obj);
			$table[$class]['count']++;
			$table[$class]['mem1'] = strlen(serialize($obj));
			$table[$class]['memory'] += $table[$class]['mem1'];
		}
		return $table;
	}

	static function getElapsedTime() {
		$profiler = $GLOBALS['profiler'];
		if ($profiler) {
			$since = $profiler->initTime;
		} else {
			$since = $_SERVER['REQUEST_TIME_FLOAT']
				? $_SERVER['REQUEST_TIME_FLOAT']
				: $_SERVER['REQUEST_TIME'];
		}
		$oaTime = microtime(true) - $since;
		$totalTime = number_format($oaTime, 3, '.', '');
		return $totalTime;
	}

	static function renderFloat() {
		$totalTime = self::getElapsedTime();
		$dbTime = 0;
		$db = class_exists('Config') ? Config::getInstance()->getDB() : NULL;
		if ($db && $db->queryLog) {
			$dbTime = ArrayPlus::create($db->queryLog)->column('sumtime')->sum();
			$dbTime = number_format($dbTime, 3, '.', '');
		}
		if (isset($db->queryLog) && is_object($db->queryLog)) {
			$dbTime = $db->queryLog->getDBTime();
			$dbTime = number_format($dbTime, 3, '.', '');
		}
		if (isset($db->queryTime)) {
			$dbTime = $db->queryTime;
			$dbTime = number_format($dbTime, 3, '.', '');
		}
		if (function_exists('session_status')
			&& session_status() == PHP_SESSION_ACTIVE) {
            // total
			$totalMax = ifsetor($_SESSION[__CLASS__]['totalMax']);
            if ($totalMax > 0) {
				$totalBar = '<img src="' . ProgressBar::getBar($totalTime / $totalMax * 100) . '" />';
			} else {
				$totalBar = '<img src="'.ProgressBar::getBar(0).'" />';
			}
            $_SESSION[__CLASS__]['totalMax'] = max($_SESSION[__CLASS__]['totalMax'], $totalTime);

            // db
            $dbMax = ifsetor($_SESSION[__CLASS__]['dbMax']);
            if ($dbMax > 0) {
                $dbBar = '<img src="'.ProgressBar::getBar($dbTime/$dbMax*100).'" />';
            } else {
				$dbBar = '<img src="'.ProgressBar::getBar(0).'" />';
			}
			$_SESSION[__CLASS__]['dbMax'] = max($_SESSION[__CLASS__]['dbMax'], $dbTime);
		} else {
			$totalBar = '';
			$totalMax = '';
			$dbTime = '';
			$dbBar = '';
			$dbMax = '';
		}

		$peakMem = number_format(memory_get_peak_usage()/1024/1024, 3, '.', '');
		$maxMem = self::return_bytes(ini_get('memory_limit'));
		$memBar = '<img src="'.ProgressBar::getBar(memory_get_peak_usage()/$maxMem*100).'" />';
		$content = '<div class="floatTimeContainer noprint">
			<div class="floatTime">
				<table>
					<tr>
						<td>PHP+DB:</td>
						<td>'.$totalTime.'s</td>
						<td>'.$totalBar.'</td>
						<td id="totalMax">'.$totalMax.'s</td>
					</tr>
					<tr>
						<td>DB:</td>
						<td>'.$dbTime.'s</td>
						<td>'.$dbBar.'</td>
						<td>'.$dbMax.'</td>
					</tr>
					<tr>
						<td>Mem:</td>
						<td>'.$peakMem.'MB</td>
						<td>'.$memBar.'</td>
						<td>'.ini_get('memory_limit').'</td>
					</tr>
					<tr>
						<td>Total:</td>
						<td id="page_load_time">'.$peakMem.'MB</td>
						<td><div id="page_load_time_bar">
							<div></div>
						</div></td>
						<td></td>
					</tr>
				</table>
			</div>
		</div>
		<div style="clear:both"></div>
		<script>
			var now = new Date().getTime();
			var page_load_time = now - performance.timing.navigationStart;
			page_load_time = (page_load_time/1000).toFixed(3);
			//console.log("User-perceived page loading time: " + page_load_time);
			document.querySelector("#page_load_time").innerHTML = page_load_time;
			var totalMax = parseFloat(document.querySelector("#totalMax").innerHTML);
			var width = Math.round(page_load_time/totalMax*100);
			//console.log(totalMax, width)
			document.querySelector("#page_load_time_bar div").style.width = width;
  		</script>
		';
		$content .= '<style>'.file_get_contents(
				dirname(__FILE__).'/../CSS/TaylorProfiler.less'
		).'</style>';
		return $content;
	}

	/**
	 * http://stackoverflow.com/a/1336624
	 * @param $val
	 * @return int|string
	 */
	static function return_bytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024*1024*1024;
				break;
			case 'm':
				$val *= 1024*1024;
				break;
			case 'k':
				$val *= 1024;
				break;
		}

		return $val;
	}

	/**
	 * @return float
	 */
	static function getMemUsage() {
		$max = self::return_bytes(ini_get('memory_limit'));
		$cur = memory_get_usage();
		return number_format($cur/$max, 3, '.', '');
	}

	static function getTimeUsage() {
		static $max;
		$max = $max ? $max : intval(ini_get('max_execution_time'));
		$cur = microtime(true) - $_SERVER['REQUEST_TIME'];
		return number_format($cur/$max, 3, '.', '');
	}

	static function getMemDiff() {
		static $prev = 0;
		$cur = memory_get_usage();
		$return = number_format(($cur-$prev)/1024/1024, 3, '.', '').'M';
		$prev = $cur;
		return $return;
	}

	static function enableTick($ticker = 1000) {
		$tp = self::getInstance();
		register_tick_function(array($tp, 'tick'));
		declare(ticks=1000);
	}

	function tick() {
		static $prev = 0;
		$bt = debug_backtrace();
		$list = array();
		$prow = array();
		foreach ($bt as $row) {
			$list[] = basename(ifsetor($row['file'])).
				((isset($row['object'])
					&& ifsetor($row['file']) != 'class.'.get_class($row['object']).'.php')
					? ('['.get_class($row['object']).']')
					: ifsetor($row['class'])
				).'::'.$row['function'].
				'#'.ifsetor($prow['line']);
			$prow = $row;
		}
		$list = array_reverse($list);
		$list = array_slice($list, 0, -1);	// cut TaylorProfiler::tick
		//$list = array_slice($list, 3);
		$mem = self::getMemUsage();
		$diff = number_format(100*($mem - $prev), 2);
		$diff = $diff >= 0
			? '<font color="green"> '.$diff.'</font>'
			: '<font color="red">'.$diff.'</font>';
		$trace = implode(' -> ', $list);
		$trace = substr($trace, -500);

		$start = ifsetor($_SERVER['REQUEST_TIME_FLOAT'], $_SERVER['REQUEST_TIME']);
		$time = number_format(microtime(true) - $start, 3, '.', '');

		$output = '<pre style="margin: 0; padding: 0;">'.
			$time.' diff: '.($diff >= 0 ? ' ' : '').$diff.' '.
			number_format($mem*100, 2).'% '.implode(' -> ', $list).'</pre>';

		if ($this->tickTo == 'html') {
			if (Request::isCLI()) {
				$output = strip_tags($output);
			}
			echo $output . "\n";
			if (sizeof($list) > 100) {
				pre_print_r($list);
				throw new Exception('Infinite loop detected');
			}
		} elseif ($this->tickTo) {
			$pad = str_pad($time, 6, '0', STR_PAD_LEFT);
			header('X-Tick-'.$pad.': '.strip_tags($output));
		}
		$prev = $mem;
	}

	static function disableTick() {
		unregister_tick_function(array(__CLASS__, 'tick'));
	}

	/**
	 * @param bool $output_enabled
	 * @param bool $trace_enabled
	 * @return null|TaylorProfiler
	 */
	public static function getInstance($output_enabled=false, $trace_enabled=false) {
		return ifsetor($GLOBALS['profiler']) instanceof self
			? $GLOBALS['profiler']
			: (
				self::$instance
				?: self::$instance = new self($output_enabled, $trace_enabled)
			);
	}

	static function dumpQueries() {
		$queryLog = class_exists('Config', false)
			? (Config::getInstance()->getDB()
				? Config::getInstance()->getDB()->queryLog
				: NULL)
			: NULL;
		if (DEVELOPMENT && $queryLog) {
			return $queryLog->dumpQueriesTP();
		}
		return NULL;
	}

	static function start($method) {
		$tp = TaylorProfiler::getInstance();
		$tp ? $tp->startTimer($method) : NULL;
	}

	static function stop($method) {
		$tp = TaylorProfiler::getInstance();
		$tp ? $tp->stopTimer($method) : NULL;
	}

}

/*
function profiler_start($name) {
    if (array_key_exists("midcom_profiler",$GLOBALS))
      $GLOBALS["midcom_profiler"]->startTimer ($name);
}

function profiler_stop($name) {
    if (array_key_exists("midcom_profiler",$GLOBALS))
      $GLOBALS["midcom_profiler"]->stopTimer ($name);
}
*/
