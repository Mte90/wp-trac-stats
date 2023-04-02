# WP Trac Stats
[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](http://www.gnu.org/licenses/gpl-3.0)   

A way to understand how much the core contributions to WordPress impact with numbers to contributors experience.

Check the blog post for the [full analysis 2022 edition](https://daniele.tech/wordpress-core-contributions-by-tickets-numbers/), [2023 edition](https://daniele.tech/2023/04/wordpress-core-contributions-by-tickets-numbers-2023-edition)!

## How to use it

Requires PHP CLI available in the machine and a shell.

### 1st step

`./total-for-months.php`

It will generate the `total-for-months.csv` file (already available in the repo to avoid multiple requests to the server).  
The script take on average 16 minutes considering when is not crashing because the Trac WordPress server reject a single request.

### 2nd step

```
wget -O tickets.csv "https://core.trac.wordpress.org/query?status=accepted&status=assigned&status=closed&status=new&status=reopened&status=reviewing&format=csv&col=id&col=summary&col=status&col=owner&col=type&col=priority&col=milestone&col=component&col=version&col=time&col=changetime&col=resolution&col=reporter&col=keywords&order=time"
```

This command will download the whole tickets collection (excluding the hidden for security reasons and spam removed during the years).  
**Also the "Last Modified date/changetime" doesn't means the closed date** as it is not possible to get this value from the export of [Trac](https://trac.edgewall.org/).

### 3rd step

`./tickets-analysis.php > report.txt`

## Where are the JSONs?

After executing the script in the json folder you will find them.

## TODO (maybe)

* Script to generate analysis using https://github.com/WordPress/wordpress-develop/graphs/contributors automatically
