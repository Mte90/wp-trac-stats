#!/usr/bin/php
<?php
$today = date("Y-m-d");
$start_time = microtime(true);

$fileHandle = fopen("tickets.csv", "r");
$rows = array();
$iterator = 0;

function average_in_days( $intervals ) {
    // Those are in seconds
    $average = array_sum($intervals) / count($intervals);
    $average = round( $average / (3600 * 24), 1 );
    return $average;
}

function average_status_date( $resolution, $echo = true ) {
    global $rows;
    $i = 0;
    // Based on https://stackoverflow.com/a/31302697/1902215
    $intervals = array();
    foreach ($rows as &$row) {
        if ( $row['Resolution'] === $resolution ) {
            $i += 1;
            $enddate = strtotime($row['Modified']);
            if ( empty( $resolution ) ) {
                $enddate = strtotime(date("Y-m-d"));
            }

            $sub = abs(strtotime($row['Created']) - $enddate);

            if ( $sub === 0 ){
                $sub = 1;
            }
            $intervals[] = $sub;
        }
    }

    $average = average_in_days( $intervals );

    $status = ' take an average of ';

    if ( empty( $resolution ) ) {
        $resolution = 'opened';
        $status = ' without status changing ';
    }

    if ( $echo ) {
        echo $i . ' Tickets with resolution "' . $resolution . '"' . $status . $average . ' days' . "\n";
    }

    return $average;
}

function components_numbers() {
    global $rows;

    $components = array();
    $components_intervals = array();
    foreach ($rows as &$row) {
        $components[$row['Component']][$row['Resolution']] = isset($components[$row['Component']][$row['Resolution']]) ? $components[$row['Component']][$row['Resolution']] += 1 : 1;
        $components_intervals[$row['Component']][$row['Resolution']][] = abs(strtotime($row['Created']) - strtotime($row['Modified']));
;
    }

    echo "\nComponents numbers\n";

    foreach ( $components as $index => $component ) {
        echo '-----' . "\n";
        echo $index . "\n";
        if ( isset( $component[''] ) ) {
            echo ' Component "opened" tickets ' . $component[''] . "\n";
            echo ' Days Average for closing ' . average_in_days( $components_intervals[$index][''] ) . ' days' . "\n";
        }

        if ( isset( $component['fixed'] ) ) {
            echo ' Component "fixed" tickets ' . $component['fixed'] . "\n";
            echo ' Days Average for closing ' . average_in_days( $components_intervals[$index]['fixed'] ) . ' days' . "\n";
        }

        if ( isset( $component['wontfix'] ) ) {
            echo ' Component "wontfix" tickets ' . $component['wontfix'] . "\n";
            echo ' Days Average for closing ' . average_in_days( $components_intervals[$index]['wontfix'] ) . ' days' . "\n";
        }
    }
}

function owner_numbers() {
    global $rows;

    $owners = array();
    foreach ($rows as &$row) {
        $owners[$row['Owner']] = array(
            'total' => isset($owners[$row['Owner']]['total']) ? $owners[$row['Owner']]['total'] += 1 : 1,
            'fixed' => isset($owners[$row['Owner']]['fixed']) && $row['Resolution'] === 'fixed' ? $owners[$row['Owner']]['fixed'] += 1 : 1,
            'invalid' => isset($owners[$row['Owner']]['invalid']) && $row['Resolution'] === '' ? $owners[$row['Owner']]['invalid'] += 1 : 1,
            'opened' => isset($owners[$row['Owner']]['opened']) && $row['Resolution'] === '' ? $owners[$row['Owner']]['opened'] += 1 : 1,
        );
    }

    echo "\nOwners numbers";
    echo "\n" . '-----' . "\n\n";
    arsort( $owners );
    foreach ( $owners as $owner => $value ) {
        if ( $value['total'] > 20 ) {
            echo ' ' . $owner . " has " . $value['total'] . " tickets, with " . $value['opened'] . " opened tickets, " . $value['invalid'] . " invalid tickets and " . $value['fixed'] . " closed tickets\n";
        }
    }
}

function various_keywords_counts( $keyword ) {
    global $rows;
    $i = 0;
    $keywords_intervals = array();
    foreach ($rows as &$row) {
        if ( array_search( $keyword, $row['Keywords'] ) ) {
            $keywords_intervals[] = abs(strtotime($row['Created']) - strtotime($row['Modified']));
            $i += 1;
        }
    }

    $average = average_in_days( $keywords_intervals );

    echo $i . ' Tickets with keyword "' . $keyword . '" average waiting time for closing ' . $average . ' days' . "\n";

    return $average;
}

function totals_for_months() {
    $fileHandle = fopen("totals-for-months.csv", "r");
    $rows = array();
    $iterator = 0;
    $tickets = array( 'date' => 0, 'newticket' => 0, 'closedticket' => 0, 'reopenedticket' => 0, 'batchmodify' => 0, 'changeset' => 0, 'milestone' => 0, 'wiki' => 0 );

    while (($row = fgetcsv($fileHandle, 0, ",")) !== FALSE) {
        if ( $iterator !== 0 ) {
            $rows[] = array( 'date' => $row[0], 'newticket' => $row[1], 'closedticket' => $row[2], 'reopenedticket' => $row[3], 'batchmodify' => $row[4], 'changeset' => $row[5], 'milestone' => $row[6], 'wiki' => $row[7] );
        }
        $iterator += 1;
    }

    foreach ($rows as &$row) {
        foreach ($row as $index => $status) {
            if ( $index !== 'date' ) {
                $tickets[$index] += $status;
            }
        }
    }

    $lines_total = count($rows);
    foreach ($tickets as $index => $status) {
        if ( $index !== 'date' && $index !== 'milestone' && $index !== 'wiki' && $index !== 'changeset' ) {
            echo 'Average 3-months "' . $index . '" ' . round( $status / $lines_total, 1 ) . " tickets\n";
        }
    }

    echo 'Average 3-months "changeset" ' . round( $tickets['changeset'] / $lines_total, 1 ) . " changesets\n";
}

while (($row = fgetcsv($fileHandle, 0, ",")) !== FALSE) {
    if ( $iterator !== 0 ) {
        $rows[] = array( 'id' => $row[0], 'Summary' => $row[1], 'Status' => $row[2], 'Owner' => empty($row[3]) ? 'nobody' : $row[3], 'Type' => $row[4], 'Priority' => $row[5], 'Milestone' => $row[6], 'Component' => $row[7], 'Version' => $row[8], 'Created' => DateTime::createFromFormat( 'm/d/Y', explode( ' ', $row[9] )[0] )->format('Y-m-d'), 'Modified' => DateTime::createFromFormat( 'm/d/Y', explode( ' ', $row[10] )[0] )->format('Y-m-d'), 'Resolution' => $row[11], 'Reporter' => $row[12], 'Keywords' => explode( ' ', $row[13] ) );
    }
    $iterator += 1;
}

echo "DISCLAIMER!\n";
echo "It is not possible to get the closed date of a ticket we will use the last modified date that is not the ideal solution, so the average in reality could be bigger!\n";
echo "Also it is not possible to know who closed the ticket with the author of the patch!\n";
echo "Don't forget that a ticket can have more then 1 changeset that will close it!\n\n";


echo "\n" . '-----------' . "\n\n";
echo 'Total public and alive tickets ' . count( $rows ) . ' on estimated ' . $rows[ count( $rows ) -1 ]['id'] . ' total created tickets with missing ' .  ( $rows[ count( $rows ) -1 ]['id'] - count( $rows ) ) . " tickets\n";
echo 'The oldest ticket is ' . $rows[0]['id'] . ' created on ' . $rows[0]['Created'] . "\n";
foreach ($rows as &$row) {
    if ( $row['Resolution'] == '' ) {
        echo 'The oldest ticket still opened is ' . $row['id'] . ' created on ' . $row['Created'] . "\n";

        break;
    }
}

echo "\n" . '-----------' . "\n\n";
average_status_date( 'fixed' );
average_status_date( 'wontfix' );
average_status_date( 'worksforme' );
average_status_date( 'invalid' );
average_status_date( '' );

echo "\nTickets by keywords";
echo "\n" . '-----------' . "\n\n";
various_keywords_counts( 'has-patch' );
various_keywords_counts( 'needs-testing' );
various_keywords_counts( 'dev-feedback' );
various_keywords_counts( 'needs-patch' );
various_keywords_counts( '2nd-opinion' );

components_numbers();
owner_numbers();

echo "\nTickets managed every 3 months average";
echo "\n" . '-----------' . "\n\n";
totals_for_months();

echo "\n\nFinished\n";
$end_time = microtime(true);
$execution_time = round($end_time - $start_time);
echo " Execution time of script = " . round($execution_time) . " sec\n";
