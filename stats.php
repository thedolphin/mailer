<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Mailer statistics</title>
<style>
#results {
    border-collapse: collapse;
    float: left;
    margin: 5px;
}

#results td, #results th {
    border: 1px solid #98bf21;
    padding: 3px 7px 2px 7px;
}

#results th {
    text-align:left;
    padding-top:5px;
    padding-bottom:4px;
    background-color:#A7C942;
    color:#fff;
}

#results tr.alt td {
    color:#000;
    background-color:#EAF2D3;
}
</style>
</head>
<body>
<table id="results">
<tr><th>campaign id</th><th>email number</th><th>action</th></tr>
<?php

$query = "select campaign,
       count(*) as number,
       case opened | (clicked << 1) | (unsubscribe << 2) | (bounced << 3)
       when 0 then 'no action'
       when 1 then 'opened'
       when 2 then 'opened and clicked'
       when 3 then 'opened and clicked'
       when 4 then 'opened and unsubscribed'
       when 5 then 'opened and unsubscribed'
       when 6 then 'opened, clicked and unsubscribed'
       when 7 then 'opened, clicked and unsubscribed'
       when 8 then 'undelivered'
       else 'unknown' end as action
    from maillog
    group by campaign, action";

require 'common.php';

try {

    $config = new config();
    $db = init_db($config);

    if(!$res = mysql_query($query))
        throw new Exception("Cannot select from maillog: " . mysql_error ($db));

    while($row = mysql_fetch_row($res)) {
        $color = $row[0] & 1 ? ' class="alt"' : '';
        print "<tr{$color}><td>{$row[0]}</td><td>{$row[1]}</td><td>{$row[2]}</td></tr>\n";
    }

    if(!$res = mysql_query('select distinct email from maillog where campaign > 0 and unsubscribe = 1 order by email'))
        throw new Exception("Cannot select from maillog: " . mysql_error ($db));

    print '</table>' ."\n". '<table id="results">' ."\n". '<tr><th>total unsubscribed: ' . mysql_num_rows($res) . '</th></tr>' . "\n";

    $counter = 0;
    while($row = mysql_fetch_row($res)) {
        $color = $counter & 1 ? ' class="alt"' : '';
        $counter++;
        print "<tr{$color}><td>{$row[0]}</td></tr>\n";
    }
/*
    if(!$res = mysql_query('select distinct email from maillog where campaign > 0 and bounced = 1 order by email'))
        throw new Exception("Cannot select from maillog: " . mysql_error ($db));

    print '</table>' ."\n". '<table id="results">' ."\n". '<tr><th>total undelivered: ' . mysql_num_rows($res) . '</th></tr>' . "\n";

    $counter = 0;
    while($row = mysql_fetch_row($res)) {
        $color = $counter & 1 ? ' class="alt"' : '';
        $counter++;
        print "<tr{$color}><td>{$row[0]}</td></tr>\n";
    }

    if(!$res = mysql_query('select distinct email from maillog where campaign > 0 and clicked = 1 order by email'))
        throw new Exception("Cannot select from maillog: " . mysql_error ($db));

    print '</table>' ."\n". '<table id="results">' ."\n". '<tr><th>total clicked: ' . mysql_num_rows($res) . '</th></tr>' . "\n";

    $counter = 0;
    while($row = mysql_fetch_row($res)) {
        $color = $counter & 1 ? ' class="alt"' : '';
        $counter++;
        print "<tr{$color}><td>{$row[0]}</td></tr>\n";
    }
*/

}

catch (Exception $e) {
    print "<!-- " . $e->getMessage() . "-->\n";
}

?>
</table>
</body>
</html>
