### March 29th, 2007 ###
  * Fixed pass-by-reference issue (#5).
  * Fixed issue in updating the profile for users without administration level. Updating the profile removed the user from groups.
### March 20th, 2007 ###
  * Added support for group description.
  * Added feedback on Cancel.
  * Fixed cancel redirect while editing group members.
  * Improved naming and feedback messages.
  * Updated version to 1.0.
### March 5th, 2007 ###
  * Added a Cancel button in edit pages.
  * Added javascript delete confirmation.
  * Fixed Table name in updateGroup.
  * Fixed bug in hasPageAccessByCriteria that was not filtering edit privileges for groups.
  * Fixed groups display while editing a page (groups had checks when they should not have).
  * Fixed read access for read restricted pages to groups with write access. These groups can now read the pages.
  * Improved explanation on how groups restriction works.
  * Improved feedback messages and title capitalization.
  * Removed deprecated method: "hasPageAccess($postID)".
  * Removed foreign Keys usage ([Issue #2](https://code.google.com/p/wp-group-restriction/issues/detail?id=#2)).
### February 26th, 2007 ###
  * Added ugGetRestrictedResources method.
  * Changed members update message to appear in the groups-members page instead of the current group page.
  * Changed ugSetGroupsAccess to return a value for success or failure.
  * Fixed ugHasAccess to work with users that are not authenticated.
  * Fixed bug when verifying if user has access if user is admin.
  * Improved comments.
### February 13th, 2007 ###
  * Fixed bug in ugHasAccess (table name was missing)
  * Added comments on top of each file indicating their function.
### February 5th, 2007 ###
  * Table headers according to wp2.1 styles
  * When no groups are available, a message is displayed insted of the table headers
  * Fixed groups display in user profile when the user does not belong to any groups
  * Edit Page screen now allows to set both edit and read access.
  * Groups order is always consistent
  * Updating a page/group access now returns to the respective global listing page
  * Group ID column removed from groups table in the backoffice
  * Fixed text stating that users without groups could access all pages (it is not what happens)
### January 25th, 2007 ###
  * Support for Wordpress 2.1
  * Wordpress 2.1 no longer needs a hack to filter pages from the back-office
  * Fixed bug in editing a group
  * Changed table creation statements (thanks to chris.hearn01)
  * Updated readMe to new version
### January 19th, 2007 ###
  * Fixed layout problem in the Users edit page in the Admin
  * Changed used variable to get user level (to a variable independent of the Wordpress tables prefix)