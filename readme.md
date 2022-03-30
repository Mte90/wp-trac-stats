# WP Trac Stats
[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](http://www.gnu.org/licenses/gpl-3.0)   

A way to understand how much the core contributions to WordPress impact with numbers to contributors experience.

Check the blog post for the [full analysis](https://daniele.tech/?p=4845&preview=true)!

## How to get the data

`./total-for-months.php`

It will generate the `total-for-months.csv` file (available in the repo to avoid multiple requests to the server).  
The script take on average 16 minutes considering when is not crashing because the Trac WordPress server reject a single request.

```
wget -O tickets.csv "https://core.trac.wordpress.org/query?status=accepted&status=assigned&status=closed&status=new&status=reopened&status=reviewing&format=csv&col=id&col=summary&col=status&col=owner&col=type&col=priority&col=milestone&col=component&col=version&col=time&col=changetime&col=resolution&col=reporter&col=keywords&order=time"
```

This command will download the whole tickets collection (excluding the hidden for security reasons and spam removed during the years).  
**Also the "Last Modified date/changetime" doesn't means the closed date** as it is not possible to get this value from the export of [Trac](https://trac.edgewall.org/).

## TODO

* Script to generate analysis using https://github.com/WordPress/wordpress-develop/graphs/contributors automatically
