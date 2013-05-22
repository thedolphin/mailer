<html>
<head>
<title>Mailer statistics</title>
</head>
<body>
<table border="1" style="float:left;">
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
        $color = $row[0] & 1 ? ' bgcolor="#E8E8E8"' : '';
        print "<tr{$color}><td>{$row[0]}</td><td>{$row[1]}</td><td>{$row[2]}</td></tr>\n";
    }

    if(!$res = mysql_query('select distinct email from maillog where campaign > 0 and unsubscribe = 1 order by email'))
        throw new Exception("Cannot select from maillog: " . mysql_error ($db));

    print '</table>' ."\n". '<table border="1" style="float:left;">' ."\n". '<tr><th>total unsubscribed: ' . mysql_num_rows($res) . '</th></tr>' . "\n";

    while($row = mysql_fetch_row($res)) {
        print "<tr><td>{$row[0]}</td></tr>\n";
    }

    if(!$res = mysql_query('select distinct email from maillog where campaign > 0 and bounced = 1 order by email'))
        throw new Exception("Cannot select from maillog: " . mysql_error ($db));

    print '</table>' ."\n". '<table border="1" style="float:left;">' ."\n". '<tr><th>total undelivered: ' . mysql_num_rows($res) . '</th></tr>' . "\n";

    while($row = mysql_fetch_row($res)) {
        print "<tr><td>{$row[0]}</td></tr>\n";
    }


}

catch (Exception $e) {
    print "<!-- " . $e->getMessage() . "-->\n";
}

?>
</table>
</body>
</html>
