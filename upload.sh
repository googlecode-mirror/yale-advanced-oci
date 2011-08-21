sed -i "s/define('FILE_PATH', 'C/\/\/define('FILE_PATH', 'C/" includes/Constants.php
sed -i "s/\/\/define('FILE_PATH', '\//define('FILE_PATH', '\//" includes/Constants.php

git add -u .
git add .
git commit -m "New version"
git push origin master

sed -i "s/\/\/define('FILE_PATH', 'C/define('FILE_PATH', 'C/" includes/Constants.php
sed -i "s/define('FILE_PATH', '\//\/\/define('FILE_PATH', '\//" includes/Constants.php
