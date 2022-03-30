# WP Trac Stats

With this project we are trying to understand the number of tickets from WordPress Trac with a period of 3 months from 2004 when the first ticket was created.

The script take on average 16 minutes considering when is not crashing because the Trac WordPress server don't reject the request.

## How to get the data

`total-for-months.php`

It will generate the `total-for-months.csv` file (available in the repo to avoid multiple requests to th server).

This command will generate the whole tickets collection (excluding the hidden for security reasons and spam removed during the years).
**Also the "Last Modified date/changetime" doesn't means the closed date** as it is not possible to get this value from the export of [Trac](https://trac.edgewall.org/).

`wget -O tickets.csv "https://core.trac.wordpress.org/query?status=accepted&status=assigned&status=closed&status=new&status=reopened&status=reviewing&format=csv&col=id&col=summary&col=status&col=owner&col=type&col=priority&col=milestone&col=component&col=version&col=time&col=changetime&col=resolution&col=reporter&col=keywords&order=time"`
