#!/usr/bin/php
<?php
$today = date("Y-m-d");
$start_time = microtime(true);
$requests_loop = 70;

function get_user_agent() {
    $options = array(
        'http'=>array(
            'method'=>"GET",
            'header'=>"Accept-language: en\r\n" .
            "Cookie: foo=bar\r\n" .
            "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0\r\n"
        )
    );

    $context = stream_context_create($options);
    return $context;
}

function previous_timeline($html) {
    $doc = new DOMDocument;
    $doc->preserveWhiteSpace = false;
    @$doc->loadHTML($html);

    $previous = $doc->getElementById('ctxtnav')->getElementsByTagName('a')->item(0)->getAttribute('href');

    return 'https://core.trac.wordpress.org' . $previous;
}

function count_tickets($html,) {
    global $today;
    $doc = new DOMDocument;
    $doc->preserveWhiteSpace = false;
    @$doc->loadHTML($html);
    $tickets = array('_date'=>$today,'newticket'=>0,'closedticket'=>0,'reopenedticket'=>0,'batchmodify'=>0,'changeset'=>0,'milestone'=>0,'wiki'=>0);
    $items = $doc->getElementById('content')->getElementsByTagName('dt');
    foreach ($items as $item) {
        $class = $item->getAttribute('class');
        if (!isset($tickets[$class])) {
            $tickets[$class] = 1;
        } else {
            $tickets[$class] += 1;
        }
    }

    return $tickets;
}

$csv = array( array('date','newticket','closedticket','reopenedticket','batchmodify','changeset','milestone','wiki') );

echo "1 page " . $today . " https://core.trac.wordpress.org/timeline\n";
$request = file_get_contents('https://core.trac.wordpress.org/timeline?from=' . $today . '&daysback=90&authors=&ticket=on&sfp_email=&sfph_mail=&update=Update', false, get_user_agent());
$tickets = count_tickets($request);
$csv[] = $tickets;
$link = previous_timeline($request);

for ($i = 0; $i <= $requests_loop; $i++) {
    $request = file_get_contents($link, false, get_user_agent());
    $today = str_replace('https://core.trac.wordpress.org/timeline?from=', '', str_replace('&daysback=90&authors=', '', $link));
    $tickets = count_tickets($request);
    $csv[] = $tickets;
    $link = previous_timeline($request);
    echo ($i + 2) . ' page ' . $today . ' ' . $link . "\n";
    sleep(4);
}

$fp = fopen('totals-for-months.csv', 'w');
foreach ($csv as $fields) {
    fputcsv($fp, $fields);
}
fclose($fp);

echo "Finished\n";
$end_time = microtime(true);
$execution_time = round($end_time - $start_time);
echo " Execution time of script = " . round($execution_time / 60) . " min\n";
