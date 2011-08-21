<?php
require_once 'includes/Constants.php';

$usage = <<<END
Usage: php FetchClasses.php --term TERM
Updates classes from OCI.

  --term TERM               Term (201101 for spring, 2011, 201102 for summer,
                              201103 for fall, etc.)
  --startid ID              Start only after encountering the provided OCI ID
  --startsubject SUBJECT    Start going down the alphabet with SUBJECT (4-letter)

END;

$valueFlags = array('--startid', '--startsubject', '--term');
$booleanFlags = array('--help');
$letterFlags = array();

$cmd = ScriptUtil::processFlags($argv, $valueFlags, $booleanFlags, $letterFlags);

if (isset($cmd['flags']['--help']) || !isset($cmd['flags']['--term'])) {
	echo $usage . "\n";
	exit;
}

$term = $cmd['flags']['--term'];

// Flags translation
$skip = false;
$startId = -1;
if (isset($cmd['flags']['--startid'])) {
	$skip = true;
	$startId = (int) $cmd['flags']['--startid'];
}

$skipSubject = false;
$startSubject = '';
if (isset($cmd['flags']['--startsubject'])) {
	$skipSubject = true;
	$startSubject = $cmd['flags']['--startsubject'];
}

$searchPost = array(
	'term' => $term,
	'GUPgroup' => 'A',
	'CourseNumber' => '',
	'ProgramSubject' => urlencode('ACCT'),
	'InstructorName' => '',
	'timeRangeFrom' => '08',
	'timeRangeTo' => '21',
	'ExactWordPhrase' => '',
	'distributionGroupOperator' => 'AND'
);



function processExamLine($line) {
	if (mb_strpos($line, 'No regular') !== false) {
		return array(
			'group' => 0,
			'date' => '1000-01-01',
			'day_of_week' => '',
			'time' => 0.0
		);
	}
	
	if (mb_strpos($line, 'HTBA') !== false) {
		return array(
			'group' => 1,
			'date' => '1000-01-01',
			'day_of_week' => '',
			'time' => 0.0
		);
	}
	
	preg_match('@\(Group ([0-9]*)\) ([0-9]*/[0-9]*/[0-9]*) ([A-Za-z]*) ([0-9.]*)@', $line, $matches);
	return array(
		'group' => $matches[1],
		'date' => date('Y-m-d', strtotime($matches[2])),
		'day_of_week' => daysOfWeekFromLetterString($matches[3]),
		'time' => timeOfDayFloatFromString($matches[4])
	);
}

function processSkillsAreasLine($line) {
	$line = explode(' ', $line);
	array_shift($line);
	array_shift($line);
	foreach ($line as &$token) {
		$token = str_replace(',', '', $token);
		$token = trim($token);
	}
	return $line;
}

function professorsFromHtmlField($field) {
	$field = str_replace('<br>', "\n", $field);
	$field = StringUtil::textFromHtml($field);
	$professors = explode("\n", $field);
	$return = array();
	foreach ($professors as &$name) {
		$name = StringUtil::textFromHtml($name);
		if (!empty($name) && $name !== 'Staff') {
			$return[] = $name;
		}
	}
	return $return;
}

function courseCodesFromHtmlField($field) {
	$field = StringUtil::textFromHtml($field);
	$courseCodes = explode('/', $field);
	
	$courseCodes[0] = str_replace("\n", ' ', $courseCodes[0]);
	$courseCodes[0] = str_replace("\r", ' ', $courseCodes[0]);
	preg_match('@^([^ ]*) ([^ ]*) ([0-9]*) \(([0-9]*)\)@', StringUtil::textFromHtml($courseCodes[0]), $matches);
	array_shift($courseCodes);
	foreach ($courseCodes as &$courseCode) {
		$courseCode = trim($courseCode);
		preg_match('@([^0-9]*)(.*)@', $courseCode, $matches2);
		$courseCode = array(
			'subject' => StringUtil::textFromHtml($matches2[1]),
			'number' => StringUtil::textFromHtml($matches2[2])
		);
	}
	
	$additionalArray = array(
		'subject' => $matches[1],
		'number' => $matches[2],
	);
	
	array_unshift($courseCodes, $additionalArray);
	
	return array(
		'oci_id' => $matches[4],
		'section' => (int) $matches[3],
		'listings' => $courseCodes
	);
}

function courseTimesFromHtmlField($field) {
	$field = explode('<br>', $field);
	$return = array();
	
	foreach ($field as $session) {
		$session = str_replace(urldecode("&nbsp;"), '', $session);
		$session = StringUtil::textFromHtml($session);
		
		$matches = array();
		preg_match('@([0-9]*)[^H]*HTBA@', $session, $matches);
				
		if (!empty($matches)) {
			if (empty($matches[1])) {
				$matches[1] = 0;
			}
			$return[] = array(
				'days' => array('HTBA'),
				'start_time' => $matches[1],
				'end_time' => $matches[1],
				'location' => ''
			);
			
		} elseif (!empty($session)) {
			$session = explode(' ', $session);
			$daysOfWeek = $session[0];
			
			$days = daysOfWeekFromLetterString($daysOfWeek);
	
			if (empty($session[1])) {
				print_r(debug_backtrace());
				print_r($field);
				print_r($session);
				exit;
			}
			$times = explode('-', $session[1]);
			$end = timeOfDayFloatFromString($times[1]);
			// Force PM if the end time has a 'p' at the end
			if (mb_strpos($times[1], 'p') !== false && mb_strpos($times[0], 'p') === false) {
				$times[0] .= 'p';
			}
			$start = timeOfDayFloatFromString($times[0]);
			
			array_shift($session);
			array_shift($session);
			$location = implode(' ', $session);
			
			$return[] = array(
				'days' => $days,
				'start_time' => $start,
				'end_time' => $end,
				'location' => $location
			);
		}
	}
	
	return $return;
}

function timeOfDayFloatFromString($string) {
	$string = trim($string);
	
	$forceAdd12 = false;
	// Force PM if the string ends with p
	if (mb_substr($string, -1) === 'p') {
		$forceAdd12 = true;
		$string = mb_substr($string, 0, -1);
	}
	
	$time = (double) $string;
	if ($time >= 1.00 && $time < 6.00 || $forceAdd12) {
		$time += 12.00;
	}
	
	return $time;
}

function daysOfWeekFromLetterString($letterString) {
	$days = array();
	$letterToDay = array('M' => 'Monday', 'T[^h]*' => 'Tuesday', 'W' => 'Wednesday', 'Th' => 'Thursday', 'F' => 'Friday');
	$letterString .= ' ';
	
	// Returns array of days
	foreach ($letterToDay as $letter => &$day) {
		if (preg_match("@{$letter}@", $letterString) === 1) {
			$days[] = $day;
		}
	}
	
	return $days;
}

function extractFieldsFromArray($fields, $array) {
	$return = array();
	foreach ($fields as &$field) {
		$return[$field] = $array[$field];
	}
	return $return;
}

function extractCourseInfo($page) {
	$courseInfo = array();
	$classDetails = explode('<table BORDER="0" CELLPADDING="0" CELLSPACING="5" WIDTH="100%" BGCOLOR="#FFFFFF">', $page);
	
	$blurbs = explode('</td>', $classDetails[1]);
	$courseInfo['description'] = StringUtil::textFromHtml($blurbs[0]); 
	$courseInfo['requirements'] = StringUtil::textFromHtml($blurbs[2]);
	
	$classDetails = explode('<td width="50%" valign="top">', $classDetails[0]);
	
	$classFields = explode('</tr>', $classDetails[1]);
	$courseInfo['title'] = StringUtil::textFromHtml(StringUtil::getBetween('<b>', '</b>', $classFields[1]));
	preg_match('@\[([^\]]*)\]@', $classFields[1], $matches);
	$courseInfo['extra_info'] = '';

	if (isset($matches[1])) {
		$courseInfo['extra_info'] = StringUtil::textFromHtml($matches[1]);
	}
	
	$courseInfo['professors'] = professorsFromHtmlField($classFields[2]);
	$courseInfo['course_codes'] = courseCodesFromHtmlField($classFields[0]);
	$courseInfo['sessions'] = courseTimesFromHtmlField($classFields[3]);
	
	$classDetails = explode('</tr>', $classDetails[2]);
	
	$courseInfo['skills'] = array();
	$courseInfo['areas'] = array();
	$courseInfo['exam'] = array(
		'group' => 0,
		'date' => '1000-01-01',
		'day_of_week' => '',
		'time' => 0.0
	);
	$courseInfo['extra_flags'] = array();
	
	$uselessDetailKeywords = array('Spring');
	
	foreach ($classDetails as &$classDetail) {
		$classDetail = StringUtil::textFromHtml($classDetail);
		
		if (mb_strpos($classDetail, 'exam') !== false) {
			$courseInfo['exam'] = processExamLine(StringUtil::textFromHtml($classDetail));
		} elseif (mb_strpos($classDetail, 'Skills') !== false) {
			$courseInfo['skills'] = processSkillsAreasLine(StringUtil::textFromHtml($classDetail));
		} elseif (mb_strpos($classDetail, 'Areas') !== false) {
			$courseInfo['areas'] = processSkillsAreasLine(StringUtil::textFromHtml($classDetail));
						
		// If we've found a flag
		} elseif (!empty($classDetail)) {
			$stopForNewItem = true;
			foreach ($uselessDetailKeywords as &$uselessDetailKeyword) {
				if (mb_strpos($classDetail, $uselessDetailKeyword) !== false) {
					$stopForNewItem = false;
					break;
				}
			}
			
			if ($stopForNewItem) {
				$courseInfo['extra_flags'][] = $classDetail;
			}
		}
	}
	
	return $courseInfo;
}

function addCourseItemsMappings($table, $courseId, $itemColumn, $items) {
	foreach ($items as &$item) {
		$infoToSet = array(
			'course_id' => $courseId,
			$itemColumn => $item
		);
		$table->setInfoArray($infoToSet);
		$success = $table->commit();
		
		if (!$success) {
			$infoToSet[$itemColumn] = mb_convert_encoding($item, 'UTF-8', 'Windows-1252');
			$table->setInfoArray($infoToSet);
			$success = $table->commit();
		}
		
		if (!$success) {
			return false;
		}
	}
	return true;
}


$log = new Log('FetchClasses.txt', LOG_PATH);

if (!is_dir(TEMP_PATH . '/FetchClasses')) {
	mkdir(TEMP_PATH . '/FetchClasses', 0755, true);
}
$curl = new YaleCurl();
$curl->setCookieFile(TEMP_PATH . '/FetchClasses/cookies.txt');
$curl->setAutoLoginParameters(CAS_USERNAME, CAS_PASSWORD);

$mysqli = ProjectFunctions::createMysqli();
MysqliUtil::prepareMysqli($mysqli);

foreach ($c['SUBJECT_CODES'] as $courseSubject) {
	if ($courseSubject === $startSubject) {
		$skipSubject = false;
	}
	if ($skipSubject) {
		continue;
	}
	
	$searchPost['ProgramSubject'] = urlencode($courseSubject);
	$log->write("Doing {$courseSubject}");
	flush();

	$page = $curl->fetchPageAndLogin(OCI_SERVER . '/oci/resultWindow.jsp', $searchPost, false, true);
	$curl->fetchPageAndLogin(OCI_SERVER . '/oci/resultFrame.jsp', null, false, true);
	$classes = explode('</tr>', $curl->fetchPageAndLogin(OCI_SERVER . '/oci/resultList.jsp', null, false, true));
	
	array_shift($classes);
	array_pop($classes);
	
	foreach ($classes as &$class) {
		preg_match('@course=([0-9]*)@', $class, $matches);
		$id = (int) $matches[1];
		
		
		// Crappy continue after break scheme
		if ($id === $startId) {
			$skip = false;
		}
		if ($skip === true) {
			$log->write("Skipping {$id}");
			flush();
			continue;
		}
		
		$class = explode('</td>', $class);
		foreach ($class as &$entry) {
			$entry = StringUtil::textFromHtml($entry);
		}
		
		$page = $curl->fetchPageAndLogin(OCI_SERVER . "/oci/resultDetail.jsp?course={$id}&term={$term}", null, false, true);
		$courseInfo = extractCourseInfo($page);

		$courseNames = new MysqlTable($mysqli, 'course_names');
		
		$existingEvaluationId = false;
		
		foreach ($courseInfo['course_codes']['listings'] as &$listing) {
			$courseNames->retrieve(
				array('subject', 'number', 'section'),
				array($listing['subject'], $listing['number'], $courseInfo['course_codes']['section']),
				array('course_id')
			);
			
			if ($courseNames->resultsExist) {
				$existingEvaluationId = $courseNames->info['course_id'];
				break;
			}
		}
		
		$courses = new MysqlTable($mysqli, 'courses');
		$infoToSet = extractFieldsFromArray(
			array('title', 'description', 'requirements', 'extra_info'),
			$courseInfo
		);
		$infoToSet['exam_group'] = $courseInfo['exam']['group'];
		if ($existingEvaluationId) {
			$infoToSet['id'] = $existingEvaluationId;
		}
		$courses->setInfoArray($infoToSet);
		$success = $courses->commit();
		if (!$success) {
			$infoToSet['description'] = mb_convert_encoding($infoToSet['description'], 'UTF-8', 'Windows-1252');
			$courses->setInfoArray($infoToSet);
			$success = $courses->commit();
		}
		
		ProjectFunctions::assertAndPrint($success, $courses);
	
		
		if (!$existingEvaluationId) {
			$existingEvaluationId = $courses->insertId;
		}
		
		$courseNames->clearInfo();
		$infoToSet = array_merge(
			extractFieldsFromArray(array('subject', 'number'), $courseInfo['course_codes']['listings'][0]),
			extractFieldsFromArray(array('section', 'oci_id'), $courseInfo['course_codes'])
		);
		$infoToSet['course_id'] = $existingEvaluationId;
		$courseNames->setInfoArray($infoToSet);
		ProjectFunctions::assertAndPrint($courseNames->commit(), $courseNames);
		
		$courseAreas = new MysqlTable($mysqli, 'course_areas');
		ProjectFunctions::assertAndPrint(addCourseItemsMappings($courseAreas, $existingEvaluationId, 'area', $courseInfo['areas']), $courseAreas);
		
		$courseSkills = new MysqlTable($mysqli, 'course_skills');
		ProjectFunctions::assertAndPrint(addCourseItemsMappings($courseSkills, $existingEvaluationId, 'skill', $courseInfo['skills']), $courseSkills);
	
		$courseProfessors = new MysqlTable($mysqli, 'course_professors');
		ProjectFunctions::assertAndPrint(addCourseItemsMappings($courseProfessors, $existingEvaluationId, 'professor', $courseInfo['professors']), $courseProfessors);
		
		
		if ($courseInfo['exam']['group'] !== 0) {
			$examGroups = new MysqlTable($mysqli, 'exam_groups');
			$infoToSet = extractFieldsFromArray(array('date', 'time'), $courseInfo['exam']);
			$infoToSet['id'] = $courseInfo['exam']['group'];
			$examGroups->setInfoArray($infoToSet);
			ProjectFunctions::assertAndPrint($examGroups->commit(), $examGroups);
		}
		
		
		$courseSessions = new MysqlTable($mysqli, 'course_sessions');
		$courseSessions->addCond('course_id', $existingEvaluationId);
		ProjectFunctions::assertAndPrint($courseSessions->executeDeleteQuery(), $courseSessions);
		
		$courseSessions->clearSelect();
		$courseSessions->setInfo('course_id', $existingEvaluationId);
		foreach ($courseInfo['sessions'] as &$session) {
			$infoToSet = extractFieldsFromArray(array('start_time', 'end_time', 'location'), $session);
			$infoToSet['course_id'] = $existingEvaluationId;
			foreach ($session['days'] as &$day) {
				$infoToSet['day_of_week'] = $day;
				$courseSessions->setInfoArray($infoToSet);
				ProjectFunctions::assertAndPrint($courseSessions->commit(), $courseSessions);
			}
		}
		
		
		$courseFlags = new MysqlTable($mysqli, 'course_flags');
		ProjectFunctions::assertAndPrint(addCourseItemsMappings($courseFlags, $existingEvaluationId, 'flag', $courseInfo['extra_flags']), $courseFlags);	

	
		$log->write("Done {$courseInfo['course_codes']['listings'][0]['subject']} {$courseInfo['course_codes']['listings'][0]['number']} {$courseInfo['course_codes']['oci_id']}");
		flush();
	}
}

