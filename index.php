<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>YOCII: A Yale OCI Alternative</title>
	<link rel="stylesheet" type="text/css" href="/css/reset.css">
	<style type="text/css">
body {
	margin: 0;
	padding: 0;
	font: 10pt Tahoma, Geneva, 'DejaVu LGC Sans Condensed', sans-serif;
}
#header {
	position: relative;
	height: 50px;
	background: #0e4c92;
	color: #fff;
}
#headerLeft {
	position: absolute;
	bottom: 0;
	left: 0;
	padding: 5px;
	font: 12pt Garamond,serif;
}
#headerRight {
	position: absolute;
	bottom: 0;
	right: 0;
	padding: 5px;
	line-height: 28px;
}
#title {
	font: 28pt Garamond,serif;
}
.clear {
	height: 0;
	width: 0;
	clear: both;
}
.smaller {
	font-size: 80%;
}
	</style>
</head>

<body>

<div id="header">
	<div id="headerLeft"><span id="title">Y<span class="smaller">OCII</span></span> A modified course information system</div>
	<div id="headerRight">
		<form action="search.php" method="get">
		<label for="quickSearch">Quick Search:</label>
		<input type="text" class="text" id="quickSearch" name="for" />
		<input type="submit" class="submit" value="Go" />
		</form>
	</div>
</div>

<div id="resultsBox"></div>
</body>
</html>