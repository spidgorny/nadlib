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
	 * Initialise the timer. with the current micro time
	 * @param bool $output_enabled
	 * @param bool $trace_enabled
	 */
    function __construct( $output_enabled=false, $trace_enabled=false) {
        $this->description = array();
        $this->startTime = array();
        $this->endTime = array();
        $this->initTime = 0;
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
		$i = 3;
		//$name = dbLayerPG::getCaller($i, 2);
		$name = MySQL::getCaller();
		return $name;
	}

	/**
	 *   Start an individual timer
	 *   This will pause the running timer and place it on a stack.
	 * @param string $name name of the timer
	 * @param string $desc
	 * @internal param \optional $string $desc description of the timer
	 */
    function startTimer($name = NULL, $desc="" ){
		$name = $name ? $name : $this->getName();
    	if ($this->trace_enabled) {
	        $this->trace[] = array('time' => time(), 'function' => "$name {", 'memory' => memory_get_usage());
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
	        $this->trace[] = array('time' => time(), 'function' => "} $name", 'memory' => memory_get_usage());
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
    *   print out a log of all the timers that were registered
    *
    */
    function printTimers($enabled=false) {
    	$table = array();
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
            }

            // add missing
            $TimedTotal = 0;
            foreach ($together as $row) {
				$TimedTotal += $row['total'];
            }
            $missed=$oaTime-$TimedTotal;
            $perc = ($missed/$oaTime)*100;
            $tot_perc+=$perc;
            $together['Missed'] = array(
            	'desc' => 'Missed',
            	'time' => number_format($missed, 2, '.', ''),
            	'total' => number_format($missed, 2, '.', ''),
            	'count' => 0,
            	'perc' => number_format($perc, 2, '.', '').'%',
            );

            uasort($together, array($this, 'sort'));

			$i = 0;
            $table = array();
			foreach ($together as $key => $row) {
	            $total = $row['total'];
                $TimedTotal += $total;
	            $perc = $row['perc'];
	            $tot_perc+=$perc;
	            $table[] = array(
	               	'nr' => ++$i,
	               	'count' => $row['count'],
	               	'time, ms' => number_format($total*1000, 2, '.', '').'',
	               	'avg/1' => number_format($row['avg'], 2, '.', '').'',
	               	'percent' => number_format($perc, 2, '.', '').'%',
	                'routine' => '<span title="'.htmlspecialchars($this->description2[$key]).'">'.$key.'</span>',
	            );
		   }

            $s = new slTable();
            $s->thes(array(
            	'nr' => 'nr',
            	'count' => array('name' => 'count', 'align' => 'right'),
            	'time, ms' => array('name' => 'time, ms', 'align' => 'right'),
            	'avg/1' => array('name' => 'avg/1', 'align' => 'right'),
            	'percent' => array('name' => 'percent', 'align' => 'right'),
            	'routine' => array('name' => 'routine', 'no_hsc' => true),
            ));
            $s->more = 'class="view_array" awidth="100%"';
            $s->data = $table;
            $s->footer = array(
            	'nr' => 'total',
            	'time, ms' => number_format($oaTime*1000, 2, '.', ''),
            	'percent' => number_format($tot_perc, 2, '.', '').'%',
            	'routine' => "OVERALL TIME",
            );
            $out = $s->getContent();
            return $out;
        }
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
			print view_table($this->trace);
        }
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
        $this->trace[] = array('time' => time(), 'function' => "... $name", 'memory' => memory_get_usage());
        $this->startTime[$name] = $this->getMicroTime();
    }

    /**
    *   suspend  an individual timer
    *
    */
    function __suspendTimer($name){
        $this->trace[] = array('time' => time(), 'function' => "$name ...", 'memory' => memory_get_usage());
        $this->endTime[$name] = $this->getMicroTime();
        if (!array_key_exists($name, $this->running))
            $this->running[$name] = $this->elapsedTime($name);
        else
            $this->running[$name] += $this->elapsedTime($name);
    }

    function getMaxMemory() {
    	$amem = array2::array_column($this->trace, 'memory');
    	if (sizeof($amem)) {
    		$ret = max($amem);
    	}
    	return $ret;
    }

	function renderFloat() {
		$oaTime = microtime(true) - ($this->initTime
				? $this->initTime
				: ($_SERVER['REQUEST_TIME_FLOAT'] ?: $_SERVER['REQUEST_TIME'])
		);
		$totalTime = number_format($oaTime, 3, '.', '');
		if (Config::getInstance()->db->queryLog) {
			$dbTime = ArrayPlus::create(Config::getInstance()->db->queryLog)->column('sumtime')->sum();
			$dbTime = number_format($dbTime, 3, '.', '');
		}
		$content = '<div class="floatTimeContainer">
		<div class="floatTime">t:'.$totalTime.'s '.
			'db:'.$dbTime.'s '.
			'mem:'.number_format(memory_get_peak_usage()/1024/1024, 3, '.', '').'MB/'.
			ini_get('memory_limit').'</div>
		</div>';
		return $content;
	}

	/**
	 * @return float
	 */
	static function getMemUsage() {
		$max = intval(ini_get('memory_limit'))*1024*1024;
		$cur = memory_get_usage();
		return number_format($cur/$max, 4, '.', '');
	}

	static function getMemDiff() {
		static $prev = 0;
		//$max = intval(ini_get('memory_limit'))*1024*1024;
		$cur = memory_get_usage();
		$return = number_format(($cur-$prev)/1024/1024, 3, '.', '').'M';
		$prev = $cur;
		return $return;
	}

	static function enableTick($ticker = 100) {
		register_tick_function(array(__CLASS__, 'tick'));
		declare(ticks=1000);
	}

	static function tick() {
		static $prev = 0;
		$bt = debug_backtrace();
		$list = array();
		foreach ($bt as $row) {
			$list[] = ($row['object'] ? get_class($row['object']) : $row['class']).'::'.$row['function'];
		}
		$list = array_reverse($list);
		$list = array_slice($list, 3);
		$mem = self::getMemUsage();
		$diff = number_format(100*($mem - $prev), 2);
		$diff = $diff > 0 ? '<font color="green">'.$diff.'</font>' : '<font color="red">'.$diff.'</font>';
		echo '<pre>'.$diff.' '.number_format($mem*100, 2).'% '.implode(' // ', $list).'</pre>'."\n";
		$prev = $mem;
	}

	static function disableTick() {
		unregister_tick_function(array(__CLASS__, 'tick'));
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
