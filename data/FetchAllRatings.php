<?php
require_once 'includes/Constants.php';

function shutDown() {
	global $pids;
	echo "Exiting\n";
	foreach ($pids as &$pid) {
		echo trim(shell_exec("kill {$pid}"));
	}
	exit;
}

$pids = array();

declare(ticks = 1);
register_shutdown_function('shutDown');
pcntl_signal(SIGTERM,'shutDown');
pcntl_signal(SIGINT, 'shutDown');

foreach ($c['SUBJECT_CODES'] as $subject) {
	$subject = escapeshellarg($subject);
	$pids[$subject] = trim(shell_exec("php FetchRatings.php --singlesubject --startsubject {$subject} >> output.txt & echo $!"));
}

while (true) {
	$toUnset = array();
	foreach ($pids as $subject => &$pid) {
		if (!file_exists("/proc/{$pid}")) {
			echo "{$subject} {$pid} done\n";
			$toUnset[] = $subject;
		}
	}
	
	foreach ($toUnset as &$key) {
		unset($pids[$key]);
	}
	
	if (empty($pids)) {
		echo "Done\n";
		exit;
	}
}

