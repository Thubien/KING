<?php

echo "PHP Server is working!\n";
echo 'Current Directory: '.__DIR__."\n";
echo 'Database file exists: '.(file_exists(__DIR__.'/database/database.sqlite') ? 'YES' : 'NO')."\n";
phpinfo();
