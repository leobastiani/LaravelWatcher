<?php

if (!isset($_GET['csv'])) {
    return ;
}

header("Content-type: text/csv");
header('Content-Disposition: filename="csv.csv"');

echo "owner,name,stars\n";
$query = mysql_query("SELECT r.*, o.name as owner_name FROM repository r INNER JOIN owner o ON r.owner_id = o.id WHERE watchers >= 100 AND stars >= 2000 ORDER BY stars DESC LIMIT 10;");
while ($row = mysql_fetch_assoc($query)) {
    echo "$row[owner_name],$row[name],$row[stars]\n";
}


exit();
