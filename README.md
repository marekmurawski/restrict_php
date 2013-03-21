Restrict PHP plugin
===================

This plugin provides way to restrict certain users or group of users (roles) from creating and editing PHP code in page parts. Essentially it checks for existence of PHP opening tags in page part content while saving the page part data.

When Restrict PHP plugin is enabled a new permission ***edit_parts_php*** and a new role ***Php Editor*** is added.

Users having administrator role can ***always*** edit and create PHP code. By default the plugin also assigns the ***edit_parts_php*** permission	to developer role (if it exists).

A side effect of this plugin is ability to make page parts containing php code read only for unauthorised users. You just put _any_ php code into part contents and users without ***edit_parts_php*** permission won't be able to alter this page part

Unauthorised users ***can't delete*** page-parts containing PHP code (new in 0.0.2).

Installation & Documentation
----------------------------

Restrict PHP Plugin can be installed into your WolfCMS by uploading it to <install location>/wolf/plugins/restrict_php and enabling it in Wolf administration panel.

### How to restrict PHP code in page parts?
You only need to enable the plugin. By default ***only administrators and developers*** can edit PHP code. This means that for example all users having only the standard Editor role are ***not allowed to***:

- add PHP code blocks into page part content
- edit page parts which already contain PHP code (created by administrator or developer)

### How to allow _certain_ users to edit PHP code?

A specific user can be granted PHP editing permission in two ways:

- by assigning ***Php Editor*** role to a selected user individually in standard Wolf Users administration tab
- by assigning ***edit_parts_php*** permission to an existing role using ***Roles Manager*** plugin by andrewmman (found in Wolf CMS repository)

Changelog
---------

###### 0.0.6

- Compatibility with **Part_Revisions** plugin - Restrict PHP is always run first

License
-------

* GPLv3 license

Disclaimer
----------

While I make every effort to deliver quality plugins for Wolf CMS, I do not guarantee that they are free from defects. They are provided “as is," and you use it at your own risk. I'll be happy if you notice me of any errors.

I'm not really programmer nor web developer, however I like programming PHP and JavaScript. In fact I'm an [architekt](http://marekmurawski.pl).