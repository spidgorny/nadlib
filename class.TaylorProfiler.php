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
    */
    function TaylorProfiler( $output_enabled=false, $trace_enabled=false)
    {
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
        $this->output_enabled = $output_enabled;
        $this->trace_enabled = $trace_enabled;
        $this->startTimer('unprofiled');
    }

    // Public Methods

	function getName() {
		$i = 3;
		$name = dbLayerPG::getCaller($i, 2);
		return $name;
	}

    /**
    *   Start an individual timer
    *   This will pause the running timer and place it on a stack.
    *   @param string $name name of the timer
    *   @param string optional $desc description of the timer
    */
    function startTimer($name = NULL, $desc="" ){
		if (!$name) {
			$name = $this->getName();
		}
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
        if ($this->output_enabled||$enabled) {
            $TimedTotal = 0;
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
            foreach ($together as $key => $row) {
				$TimedTotal += $row['total'];
            }
            $missed=$oaTime-$TimedTotal;
            $perc = ($missed/$oaTime)*100;
            $tot_perc+=$perc;
            // $perc=sprintf("%3.2f", $perc );
            $together[] = array(
            	'desc' => 'Missed',
            	'time' => number_format($missed, 2, '.', ''),
            	'total' => number_format($missed, 2, '.', ''),
            	'count' => 0,
            	'perc' => number_format($perc, 2, '.', '').'%',
            );

            uasort($together, array($this, 'sort'));

            $table = array();
			foreach ($together as $key => $row) {
			    $val = $row['desc'];
	            $t = $row['time'];
	            $total = $row['total'];
                $TimedTotal += $total;
				$count = $row['count'];
	            $perc = $row['perc'];
	            $tot_perc+=$perc;
	            $table[] = array(
	               	'nr' => ++$i,
	               	'count' => '<div align="right">'.$row['count'].'</div>',
	               	'time, ms' => '<div align="right">'.number_format($total*1000, 2, '.', '').'</div>',
	               	'avg/1' => '<div align="right">'.number_format($row['avg'], 2, '.', '').'</div>',
	               	'percent' => '<div align="right">'.number_format($perc, 2, '.', '').'</div>',
	                'routine' => '<span title="'.htmlspecialchars($this->description2[$key]).'">'.$key.'</span>',
	            );
		   }

            // add missing
            $missed=$oaTime-$TimedTotal;
            $perc = ($missed/$oaTime)*100;
            $tot_perc+=$perc;
            $table[] = array(
            	'time, ms' => '<div align="right">'.number_format($missed*1000, 2, '.', '').'</div>',
            	'percent' => '<div align="right">'.number_format($perc, 2, '.', '').'</div>',
            	'routine' => 'Missed',
            );

            $s = new slTable();
            $s->thes(array(
            	'nr' => 'nr',
            	'count' => array('name' => 'count', 'more' => 'align="right"'),
            	'time, ms' => array('name' => 'time, ms', 'more' => 'align="right"'),
            	'percent' => array('name' => 'percent', 'more' => 'align="right"'),
            	'routine' => 'routine',
            ));
            $s->more = 'class="view_array"';
            $s->data = $table;
            $s->footer = array(
            	'nr' => 'total',
            	'time, ms' => number_format($oaTime*1000, 2, '.', ''),
            	'percent' => number_format($tot_perc, 2, '.', '').'%',
            	'routine' => "OVERALL TIME",
            );
            $out .= $s->getContent();
            $this->totalTime = number_format($oaTime*1000, 2, '.', '');
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
    function getMicroTime(){
        //Function split() is deprecated - commented and replaced split() with explode() 2011/07/12 - Soeren Klein
        //$tmp=split(" ",microtime());
        $tmp=explode(" ",microtime());
        $rt=$tmp[0]+$tmp[1];
        return $rt;
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
		$content .= '<div class="floatTime">'.$this->totalTime.'</div>';
		return $content;
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
