<?php
$conn = mysql_connect('lw-mysql', 'root', 'root');
mysql_select_db('LaravelWatcher');

function _json_decode($data) {
    return json_decode($data, true);
}

function _json_encode($data) {
    return json_encode($data, JSON_PRETTY_PRINT);
}

$URLs = [
    'laravel' => 'https://api.github.com/users/laravel/repos',
    'symfony' => 'https://api.github.com/users/symfony/repos',
];

$hasCache = file_exists('cache');
if (!$hasCache) {
    mkdir('cache');
}

function loadPage($repo, $page) {
    global $URLs, $hasCache;
    if ($page > 1) {
        $pagination = "?page=$page";
    }
    $url = $URLs[$repo];
    $url .= $pagination;

    $cache_path = "cache/{$repo}_{$page}";
    if ($hasCache) {
        if (!file_exists($cache_path)) {
            return [];
        }
        $data = file_get_contents($cache_path);
    }
    else {
        // $data from Internet
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, 'CURL');
        $data = curl_exec($curl);
        curl_close($curl);
    }
    $ret = _json_decode($data);
    if ($ret) {
        file_put_contents($cache_path, $data);
    }
    return $ret;
}

function loadLaravelPage($page) {
    return loadPage('laravel', $page);
}
function loadSymfonyPage($page) {
    return loadPage('symfony', $page);
}

$fns = [loadLaravelPage, loadSymfonyPage];

// if I have rows in Repository
// I don't want to fill
$totalRows = mysql_result(mysql_query("SELECT COUNT(*) FROM repository"), 0);
if ($totalRows == 0) {
    foreach ($fns as $fn) {
        for ($i=1; ; $i++) {
            $results = $fn($i);
            if (count($results) == 0) {
                // it's done
                break;
            }

            foreach ($results as $repo) {
                $owner      = $repo['owner'];
                $owner_name = $repo['owner']['login'];
                $name       = $repo['name'];
                $watchers   = $repo['watchers'];
                $forks      = $repo['forks'];
                $stars      = $repo['stargazers_count'];
                $url        = $repo['url'];

                // search for this owner
                while (true) {
                    $row_owner = mysql_fetch_assoc(mysql_query("SELECT * FROM owner WHERE name = '$owner_name'"));
                    if (!$row_owner) {
                        mysql_query("INSERT INTO owner (name) VALUES ('$owner_name');");
                        continue;
                    }
                    break;
                }

                mysql_query("INSERT INTO repository (owner_id, name, watchers, forks, stars, url) VALUES ('$row_owner[id]', '$name', '$watchers', '$forks', '$stars', '$url')");
            }
        }
    }
}


$queryAllRepos = mysql_query("SELECT r.*, o.name as owner_name FROM repository r INNER JOIN owner o ON r.owner_id = o.id");

include 'csv.php';

?>

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.18/datatables.min.css"/>

    <title>LaravelWatcher</title>
  </head>
  <body>
    <table id="example" class="data-tables display" style="width:100%">
        <thead>
            <tr>
                <th>Name</th>
                <th>Owner</th>
                <th>Watchers</th>
                <th>Forks</th>
                <th>Stars</th>
                <th>URL</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = mysql_fetch_assoc($queryAllRepos)) {
                ?>
                <tr>
                    <td><?=$row[name]?></td>
                    <td><?=$row[owner_name]?></td>
                    <td><?=$row[watchers]?></td>
                    <td><?=$row[forks]?></td>
                    <td><?=$row[stars]?></td>
                    <td><?=$row[url]?></td>
                </tr>
                <?
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <th>Name</th>
                <th>Owner</th>
                <th>Watchers</th>
                <th>Forks</th>
                <th>Stars</th>
                <th>URL</th>
            </tr>
        </tfoot>
    </table>

    <a href="?csv" class="btn btn-primary">Download CSV</a>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.18/datatables.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('.data-tables').DataTable();
        });
    </script>
  </body>
</html>
