<?php
require_once 'includes/Constants.php';

$usage = <<<END
Usage: php FetchRatings.php
Updates classes from OCI.

  --singlesubject           Stop after finishing the given subject
  --startnumber NUMBER      Start only after encountering the provided
                              course number (with the rest)
  --startseason SEASON      Start only after encountering the provided
                              season (with the rest)
  --startsection SECTION    Start only after encountering the provided section (with the rest)
  --startsubject SUBJECT    Start going down the alphabet with SUBJECT (4-letter usually)
  --endnumber, --endseason, --endsection, --endsubject are analogous
  --noecho                  Don't echo status

END;

$valueFlags = array(
	'--startnumber', '--startsubject', '--startseason', '--startsection',
	'--endnumber', '--endsubject', '--endseason', '--endsection'
);
$booleanFlags = array('--help', '--singlesubject', '--noecho');
$letterFlags = array();

$cmd = ScriptUtil::processFlags($argv, $valueFlags, $booleanFlags, $letterFlags);

if (isset($cmd['flags']['--help'])) {
	echo $usage . "\n";
	exit;
}


// Flags translation
$skip = false;
$startInfo = array();
if (isset($cmd['flags']['--startnumber'])
	&& isset($cmd['flags']['--startseason'])
	&& isset($cmd['flags']['--startsection'])
	&& isset($cmd['flags']['--startsubject']))
{
	$skip = true;
	$startInfo = array(
		'number' => $cmd['flags']['--startnumber'],
		'season' => $cmd['flags']['--startseason'],
		'section' => (int) $cmd['flags']['--startsection'],
		'subject' => $cmd['flags']['--startsubject']
	);
}

$endInfo = array();
if (isset($cmd['flags']['--endnumber'])
	&& isset($cmd['flags']['--endseason'])
	&& isset($cmd['flags']['--endsection'])
	&& isset($cmd['flags']['--endsubject']))
{
	$endInfo = array(
		'number' => $cmd['flags']['--endnumber'],
		'season' => $cmd['flags']['--endseason'],
		'section' => (int) $cmd['flags']['--endsection'],
		'subject' => $cmd['flags']['--endsubject']
	);
}

$skipSubject = false;
$startSubject = '';
if (isset($cmd['flags']['--startsubject'])) {
	$skipSubject = true;
	$startSubject = $cmd['flags']['--startsubject'];
}

$logFlags = Log::FLAG_ECHO;
if (isset($cmd['flags']['--noecho'])) {
	$logFlags = 0;
}



// Data structures for repeated stuff
$qidPrefixes = array('viewevals' => 'YC', 'summerevals' => 'SU');

$radioButtonPages['difficulty_'] = array(
	'qid_number' => '005',
	'labels' => array('Much Less' => 1, 'Less' => 2, 'Same' => 3, 'Greater' => 4, 'Much Greater' => 5)
);
$radioButtonPages['rating_'] = array(
	'qid_number' => '006',
	'labels' => array('Poor' => 1, 'Below Average' => 2, 'Good' => 3, 'Very Good' => 4, 'Excellent' => 5)
);
$radioButtonPages['major_'] = array(
	'qid_number' => '007',
	'labels' => array('Yes' => 1, 'No' => 0)
);
$radioButtonPages['requirements_'] = array(
	'qid_number' => '008',
	'labels' => array('Yes' => 1, 'No' => 0)
);

$commentPages = array('001', '002', '004');

function extractCourseInfo($page) {
	$return = array();
	
	$pageSections = explode('<td colspan="2" align="center"', $page);
	array_shift($pageSections);

	foreach ($pageSections as &$pageSection) {
		$pageSection = StringUtil::getBetween('class="heading2">', '</td>', $pageSection);
		$pageSection = StringUtil::textFromHtml($pageSection);

		preg_match('@([0-9]*)[^A-Z]*([^ ]*)[^A-Za-z0-9]*([^ ]*)[^0-9]*([0-9]*)[^A-Za-z0-9]*(.*)@', $pageSection, $match);
		$return[] = array(
			'oci_id' => $match[1],
			'subject' => StringUtil::textFromHtml($match[2]),
			'number' => $match[3],
			'section' => (int) $match[4],
			'short_title' => StringUtil::textFromHtml($match[5])
		);
	}
	
	return $return;
}

function professorsFromField($professors) {
	$professors = explode(',', $professors);
	foreach ($professors as &$professor) {
		$professor = StringUtil::textFromHtml($professor);
	}
	return $professors;
}


function getRadioCheckedFrequenciesInPage($labelsAndReturnKeys, $page) {
	$return = array();
	foreach ($labelsAndReturnKeys as $label => &$key) {
		$count = preg_match_all("@<input checked[^>]*><[^>]*>{$label}</span>@", $page, $matches);
		$return[$key] = $count;
	}
	
	return $return;
}


function extractFieldsFromArray($fields, $array) {
	$return = array();
	foreach ($fields as &$field) {
		$return[$field] = $array[$field];
	}
	return $return;
}


function getCommentsFromPage($page) {
	preg_match_all('@<p class="content-text">([^<]*)</p>@', $page, $matches);
	
	foreach ($matches[1] as &$comment) {
		$comment = StringUtil::textFromHtml($comment);
	}
	
	return $matches[1];
}

function extractEvaluationInfo($page, $professors, $type) {
	$evaluationInfo = array();
	$evaluationInfo['courses'] = extractCourseInfo($page);
	$evaluationInfo['season'] = $season;
	$evaluationInfo['professors'] = professorsFromField($professors);
	
	foreach ($radioButtonPages as $columnPrefix => &$pageType) {
		$page = $curl->fetchPageAndLogin("https://faculty.yale.edu/viewevals/Search/ViewAnswersByQuestion?subType=all&ap=0&qid={$qidPrefixes[$type]}{$pageType['qid_number']}&na=10000", null, false, true);
		
		if (!empty($page)) {
			$responses = getRadioCheckedFrequenciesInPage($pageType['labels'], $page);
			
			foreach ($responses as $columnSuffix => &$response) {
				$evaluationInfo[$columnPrefix . $columnSuffix] = $response;
			}
		}
	}
	
	$evaluationInfo['comments'] = array();
	foreach ($commentPages as &$qidNumber) {
		$page = $curl->fetchPageAndLogin("https://faculty.yale.edu/viewevals/Search/ViewAnswersByQuestion?subType=all&ap=0&qid={$qidPrefixes[$type]}{$qidNumber}&na=10000", null, false, true);
		if (empty($page)) {
			continue;
		}
		$comments = getCommentsFromPage($page);
		if (!empty($comments)) {
			$evaluationInfo['comments'][(int) $qidNumber] = $comments;
		}
	}
}

function addCourseItemsMappings($table, $courseId, $itemColumn, $items, $extraInfo) {
	foreach ($items as &$item) {
		$infoToSet = $extraInfo;
		$infoToSet['course_id'] = $courseId;
		$infoToSet[$itemColumn] = $item;
		
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


// Log
$log = new Log('FetchRatings.txt', LOG_PATH);

// YaleCurl
$curl = new YaleCurl();
$cookieFileName = 'cookies';
if (!empty($startSubject)) {
	$cookieFileName = $startSubject;
}

if (!is_dir(TEMP_PATH . '/FetchRatings')) {
	mkdir(TEMP_PATH . '/FetchRatings', 0755, true);
}
//var_dump($curl->setCookieFile(TEMP_PATH . "/FetchRatings/{$cookieFileName}.txt"));
//echo TEMP_PATH . "/FetchRatings/{$cookieFileName}.txt\n";
$curl->setAutoLoginParameters(CAS_USERNAME, CAS_PASSWORD);

$mysqli = ProjectFunctions::createMysqli();
MysqliUtil::prepareMysqli($mysqli);

$pid = getmypid();

foreach ($c['SUBJECT_CODES'] as $courseSubject) {
	if ($courseSubject === $startSubject) {
		$skipSubject = false;
	}
	if ($skipSubject) {
		continue;
	}
	
	$log->write("{$pid} Processing {$courseSubject}", E_NOTICE, $logFlags);
	
	$rawCourseSubject = $courseSubject;
	$courseSubject = urlencode($courseSubject);
	$subjectPage = $curl->fetchPageAndLogin("https://students.yale.edu/evalsearch/Search?sc={$courseSubject}", null, false, true);
	$matches1 = array();
	$matches2 = array();
	preg_match_all('@([a-z]*Evals)\(([0-9]*),([0-9]*)\);">[^<]*</a></td><td nowrap>[^<]*</td><td>[^<]*</td><td>([^ ]*)[^0-9]([^<]*)</td><td>([^<]*)@', $subjectPage, $matches1, PREG_SET_ORDER);
	preg_match_all('@([a-z]*Evals)\(([0-9]*),([0-9]*)\);">[^<]*</a></td><td nowrap>[^<]*</td><td>[^<]*</td><td>([^ ]*)[^0-9]([^<]*)<span[^<]*</span>[^<]*<div[^<]*</div>[^<]*</td><td>([^<]*)@', $subjectPage, $matches2, PREG_SET_ORDER);

	$evaluationData = array_merge($matches1, $matches2);
	
	foreach ($evaluationData as &$evaluation) {
		$crn = (int) $evaluation[2];
		$season = $evaluation[3];
		$type = mb_strtolower($evaluation[1]);
		$professors = $evaluation[6];

		$thisListing = array();
		$thisListing['number'] = $evaluation[4];
		$thisListing['section'] = (int) $evaluation[5];
		$thisListing['season'] = $season;
		$thisListing['subject'] = $rawCourseSubject;
	
		
		// Check if we should stop skipping
		if ($skip === true) {
			$stopSkipping = true;
			foreach ($thisListing as $key => &$value) {
				if ($value !== $startInfo[$key]) {
					$stopSkipping = false;
				}
			}
			
			if ($stopSkipping) {
				$skip = false;
			}
		}
		
		if ($thisListing === $endInfo) {
			echo "Ending\n";
		}
		
		if ($skip === true) {
			$log->write("{$pid} Skipping {$rawCourseSubject} {$thisListing['number']} {$thisListing['section']} {$season} {$crn}", E_NOTICE, $logFlags);
			continue;
		}
		
		// This part gets the info
		$page = $curl->fetchPageAndLogin("https://faculty.yale.edu/{$type}/Search/Summary?crn={$crn}&tC={$season}", null, true, true);
		$evaluationInfo['courses'] = extractCourseInfo($page);
		
		$evaluationInfo['season'] = $season;
		$evaluationInfo['professors'] = professorsFromField($professors);
		
		foreach ($radioButtonPages as $columnPrefix => &$pageType) {
			$page = $curl->fetchPageAndLogin("https://faculty.yale.edu/viewevals/Search/ViewAnswersByQuestion?subType=all&ap=0&qid={$qidPrefixes[$type]}{$pageType['qid_number']}&na=10000", null, false, true);
			
			$responses = getRadioCheckedFrequenciesInPage($pageType['labels'], $page);
				
			foreach ($responses as $columnSuffix => &$response) {
				$evaluationInfo['statistics'][$columnPrefix . $columnSuffix] = $response;
			}
		}
		
		$evaluationInfo['comments'] = array();
		foreach ($commentPages as &$qidNumber) {
			$page = $curl->fetchPageAndLogin("https://faculty.yale.edu/viewevals/Search/ViewAnswersByQuestion?subType=all&ap=0&qid={$qidPrefixes[$type]}{$qidNumber}&na=10000", null, false, true);
			if (empty($page)) {
				continue;
			}
			$comments = getCommentsFromPage($page);
			if (!empty($comments)) {
				$evaluationInfo['comments'][(int) $qidNumber] = $comments;
			}
		}
		
				
		// This part commits to the database
		$existingEvaluationId = false;
		
		$courseNames = new MysqlTable($mysqli, 'evaluation_course_names');
		foreach ($evaluationInfo['courses'] as &$course) {
			$courseNames->retrieve(
				array('subject', 'number', 'section', 'season'),
				array($course['subject'], $course['number'], $course['section'], $season),
				array('course_id')
			);
						
			if ($courseNames->resultsExist) {
				$existingEvaluationId = $courseNames->info['course_id'];
				break;
			}
		}
		
		
		$courses = new MysqlTable($mysqli, 'evaluation_courses');

		$infoToSet = $evaluationInfo['statistics'];
		$infoToSet['season'] = $evaluationInfo['season'];
		if ($existingEvaluationId) {
			$infoToSet['id'] = $existingEvaluationId;
		}
		$courses->setInfoArray($infoToSet);
		ProjectFunctions::assertAndPrint($courses->commit(), $courses);
		
		if (!$existingEvaluationId) {
			$existingEvaluationId = $courses->insertId;
		}
		
		
		foreach ($evaluationInfo['courses'] as &$course) {
			$courseNames->clearInfo();
			$infoToSet = extractFieldsFromArray(array('subject', 'number', 'section'), $course);
			$infoToSet['course_id'] = $existingEvaluationId;
			$infoToSet['season'] = $evaluationInfo['season'];
			$courseNames->setInfoArray($infoToSet);
			ProjectFunctions::assertAndPrint($courseNames->commit(), $courseNames);
		}
		
		
		$evaluationComments = new MysqlTable($mysqli, 'evaluation_comments');
		foreach ($evaluationInfo['comments'] as $type => &$comments) {
			$success = addCourseItemsMappings($evaluationComments, $existingEvaluationId, 'comment', $comments, array('type' => $type));
			if (!$success) {
				echo "php FetchRatings.php --startsubject {$rawCourseSubject} --startnumber {$thisListing['number']} --startsection {$thisListing['section']} --startseason {$season}";
			}
			ProjectFunctions::assertAndPrint($success, $evaluationComments->error);
		}
		
		$curCourse = $evaluationInfo['courses'][0];
		$log->write("{$pid} Done {$rawCourseSubject} {$thisListing['number']} {$thisListing['section']} {$season} {$crn}", E_NOTICE, $logFlags);
	}
	
	if (isset($cmd['flags']['--singlesubject'])) {
		exit;
	}
}
