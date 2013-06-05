Script to check file existance differences.

Aim
===
Easily check some file is missing instead of searching for a D in svn st
Color Coded

Motivation
==========
Check against svn branch file status colour coded.
Easily spot a missing file when "svn st" is printing too much information

Usage
=====
svnlist

Planned improvements
====================
- option to select another URL to check against, to check current branch againgst another, f.e., relative route on current
- orange for modified files (might come with a time cost)
- option to recursively check directories (same as above)
