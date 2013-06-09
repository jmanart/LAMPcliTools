This script is a fast helper to check syntax on a directory structure prior to commiting.

Aim
---
Being able to perform a check on all the files under a directory.

Motivation
----------
Overcoming the lack of integration in svn pre commits
Avoid silly mistakes on fast fixes
Type less

Usage
-----
chk [OPTIONS]
Available options:
   -s       checks only svn diff output files

Variations
----------
"CHECK_CMD" and "CHECK_EXT" both can be changed for any other language