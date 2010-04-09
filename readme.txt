=== WP Admin Microblog ===
Contributors: Michael Winkler
Tags: microblog, microblogging, admin, communication
Requires at least: 2.8
Tested up to: 2.9.2
Stable tag: 0.6.0

WP Admin Microblog adds a seperate microblog in your WordPress backend.

== Description ==

WP Admin Microblog adds a seperate microblog in your WordPress backend. The plugin has an integrated url replacing, supports some bbcodes, tagging and it's possible to send a message via e-mail to other users. Now, with the new dashboard widget, you can read, respond, edit and delete messages directly. So, WP Admin Microblog is great for supporting the communication within blog teams or it's a nice scratchpad. ;-)

= Supported Languages =
* English 
* Deutsch

= Disclaimer =  
Use at your own risk. No warranty expressed or implied is provided. 

== Credits ==

Copyright 2010 by Michael Winkler

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

== Installation ==

1. Download the plugin.
2. Extract all the files. 
3. Upload everything (keeping the directory structure) to your plugins directory.
4. Activate the plugin through the 'plugins' menu in WordPress.

== Screenshots ==

1. The interface
2. The dashboard widget

== Frequently Asked Questions ==

= How can I send a message as e-mail notification? =
If you will send your message via e-mail to any user, so write @username (example: @admin).

= Can I add tags directly with a '#' before the tag ? =
No, sorry. Inline tagging is comming soon.

== Changelog ==
= 0.6.0 =
* New: Dashboard widget
= 0.5.3 =
* Bugfix: Fixed a problem with `<br />` tags, when editing messages
* Bugfix: Fixed some security vulnerabilities (SQL injections)
* Bugfix: Prevent sending of clean messages
= 0.5.2 =
* New: Possible to use bbcodes for bold, italic, strikethrough and underline
* Bugfix: Fixed two security vulnerabilities (XSS)
= 0.5.1 =
* Changed: The plugin gives an warning if there are no tags in the database --> prevent a SQL error
* Bugfix: Fixed an installer bug
= 0.5.0 =
* First public release