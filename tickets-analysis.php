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
            $intervals[] = abs(strtotime($row['Created']) - strtotime($row['Modified']));
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

function various_counts(){
    global $rows;


    $components = array();
    $components_intervals = array();
    $owners = array();
    foreach ($rows as &$row) {
        $components[$row['Component']][$row['Resolution']] = isset($components[$row['Component']][$row['Resolution']]) ? $components[$row['Component']][$row['Resolution']] += 1 : 1;
        $components_intervals[$row['Component']][$row['Resolution']][] = abs(strtotime($row['Created']) - strtotime($row['Modified']));
        $owners[$row['Owner']] = array(
            'total' => isset($owners[$row['Owner']]['total']) ? $owners[$row['Owner']]['total'] += 1 : 1,
            'fixed' => isset($owners[$row['Owner']]['fixed']) && $row['Resolution'] === 'fixed' ? $owners[$row['Owner']]['fixed'] += 1 : 1,
            'invalid' => isset($owners[$row['Owner']]['invalid']) && $row['Resolution'] === '' ? $owners[$row['Owner']]['invalid'] += 1 : 1,
            'opened' => isset($owners[$row['Owner']]['opened']) && $row['Resolution'] === '' ? $owners[$row['Owner']]['opened'] += 1 : 1,
        );
    }

    echo "\nComponents numbers\n";

    foreach ( $components as $index => $component ) {
        echo '-----' . "\n";
        echo $index . "\n";
        if ( isset( $component[''] ) ) {
            echo ' Component "opened" tickets ' . $component[''] . "\n";
            echo ' Average of ' . average_in_days( $components_intervals[$index][''] ) . ' days' . "\n";
        }

        if ( isset( $component['fixed'] ) ) {
            echo ' Component "fixed" tickets ' . $component['fixed'] . "\n";
            echo ' Average of ' . average_in_days( $components_intervals[$index]['fixed'] ) . ' days' . "\n";
        }

        if ( isset( $component['wontfix'] ) ) {
            echo ' Component "wontfix" tickets ' . $component['wontfix'] . "\n";
            echo ' Average of ' . average_in_days( $components_intervals[$index]['wontfix'] ) . ' days' . "\n";
        }
    }

    echo "\nOwners numbers\n";

    echo "\n" . '-----' . "\n\n";
    arsort( $owners );
    foreach ( $owners as $owner => $value ) {
        if ( $value['total'] > 20 ) {
            echo ' ' . $owner . " has " . $value['total'] . " tickets, with " . $value['opened'] . " opened tickets, " . $value['invalid'] . " invalid tickets and " . $value['fixed'] . " closed tickets\n";
        }
    }
}

while (($row = fgetcsv($fileHandle, 0, ",")) !== FALSE) {
    if ( $iterator !== 0 ) {
        $rows[] = array( 'id' => $row[0], 'Summary' => $row[1], 'Status' => $row[2], 'Owner' => empty($row[3]) ? 'nobody' : $row[3], 'Type' => $row[4], 'Priority' => $row[5], 'Milestone' => $row[6], 'Component' => $row[7], 'Version' => $row[8], 'Created' => DateTime::createFromFormat( 'm/d/Y', explode( ' ', $row[9] )[0] )->format('Y-m-d'), 'Modified' => DateTime::createFromFormat( 'm/d/Y', explode( ' ', $row[10] )[0] )->format('Y-m-d'), 'Resolution' => $row[11], 'Reporter' => $row[12], 'Keywords' => explode( ' ', $row[13] ) );
    }
    $iterator += 1;
}

echo 'Total public and alive tickets ' . count( $rows ) . ' on estimated ' . $rows[ count( $rows ) -1 ]['id'] . ' total created tickets with missing ' .  ( $rows[ count( $rows ) -1 ]['id'] - count( $rows ) ) . " tickets\n";
echo 'The oldest ticket is ' . $rows[0]['id'] . ' created on ' . $rows[0]['Created'] . "\n";

echo "\n" . '-----------' . "\n\n";
echo "It is not possible to get the closed date of a ticket we will use the last modified date that is not the ideal solution, so the average in reality could be bigger!\n";
echo "Also it is not possible to know who closed the ticket with the author of the patch!\n";

average_status_date( 'fixed' );
average_status_date( 'wontfix' );
average_status_date( 'worksforme' );
average_status_date( 'invalid' );
average_status_date( '' );

various_counts();

echo "\n\nFinished\n";
$end_time = microtime(true);
$execution_time = round($end_time - $start_time);
echo " Execution time of script = " . round($execution_time / 60) . " min\n";
