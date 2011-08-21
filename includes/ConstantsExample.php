<?php
define('FILE_PATH', dirname(__FILE__) . '/..');
define('LOG_PATH', FILE_PATH . '/logs');
define('TEMP_PATH', FILE_PATH . '/temp');

define('MYSQL_HOST', 'localhost'); // e.g. define('MYSQL_HOST', 'example.com');
define('MYSQL_USERNAME', '');
define('MYSQL_PASSWORD', '');
define('MYSQL_DATABASE', '');

define('OCI_SERVER', 'http://students.yale.edu');
define('CAS_USERNAME', 'ENTER YALE CAS USERNAME HERE (e.g. abc23)');
define('CAS_PASSWORD', 'ENTER YALE CAS PASSWORD HERE (e.g. MyPass01)');

$c = get_defined_constants();

$c['SUBJECT_CODES'] = array('ACCT', 'AFAM', 'AFST', 'AKKD', 'AMST', 'ANTH', 'AMTH', 'APHY', 'ARBC', 'ARCG', 'ARCH', 'ART', 'ASTR', 'B&BS', 'BIOL', 'BENG', 'BIS', 'BRST', 'C&MP', 'CBIO', 'CENG', 'CHEM', 'CHLD', 'CHNS', 'CDE', 'CLCV', 'CL&L', 'CLSS', 'CGSC', 'CSBK', 'CSBR', 'CSCC', 'CSDC', 'CSES', 'CSJE', 'CSMC', 'CSPC', 'CSSY', 'CSSM', 'CSTD', 'CSTC', 'CSEM', 'CB&B', 'CPLT', 'CPSC', 'CPAR', 'CEU', 'CPTC', 'CZEC', 'DEVN', 'DRST', 'DISA', 'DISR', 'DIVN', 'DRAM', 'DRMA', 'EALL', 'EAST', 'E&EB', 'ECON', 'EDUC', 'EGYP', 'EENG', 'ENG', 'ENAS', 'ENGL', 'ELP', 'ESL', 'ENVB', 'ENVN', 'ENVE', 'EHS', 'EVST', 'EPH', 'EMD', 'EP&E', 'ER&M', 'E&RS', 'FILM', 'F&ES', 'FREN', 'GENE', 'G&G', 'GMAN', 'GMST', 'GHD', 'GREK', 'MGRK', 'HPA', 'HLTH', 'HEBR', 'HELN', 'HNDI', 'HSHM', 'HIST', 'HSAR', 'HUMS', 'IBIO', 'IDRS', 'INDC', 'INDN', 'INRL', 'INTS', 'IMED', 'ITAL', 'JAPN', 'JDST', 'SWAH', 'KREN', 'LATN', 'LAST', 'LAW', 'LING', 'LITR', 'MGTS', 'MGMT', 'MGT', 'MRES', 'MATH', 'MENG', 'MEDR', 'MEDC', 'MDVL', 'MESO', 'MBIO', 'MMES', 'MB&B', 'MCDB', 'MUSI', 'MUS', 'NHTL', 'NELC', 'NBIO', 'NSCI', 'NURS', 'OPRS', 'YPKU', 'PATH', 'PERS', 'PHAR', 'PHIL', 'PA', 'PHYS', 'PLSH', 'PLSC', 'PORT', 'CAND', 'PSYC', 'QUAL', 'QUAN', 'REL', 'RLST', 'RNST', 'RUSS', 'RSEE', 'SKRT', 'SCIE', 'SMTC', 'SLAV', 'SLL', 'SOCY', 'SAST', 'SEAS', 'SPAN', 'SPEC', 'SPTC', 'STAT', 'STEV', 'STCY', 'SYRC', 'TAML', 'TPRP', 'THST', 'TKSH', 'VIET', 'VAIR', 'WGSS', 'YORU', 'ZULU');

function __autoload($className) {
        require_once "classes/{$className}.php";
}

