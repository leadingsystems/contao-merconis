<?php
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-01'] = '
<h2>Congratulations,</h2>
<p>You made a good choice and installed Merconis in your Contao project.</p>
<p>The Merconis setup wizard will now automatically create all required pages, 
articles, modules and forms in your existing Contao project and insert demo data.</p>
<p class="shopHint"><strong>Note:</strong> Please create a complete backup of your project
first - database as well as project files. Normally the setup runs smoothly but if in an
exceptional case a problem should occur during setup, it is important that you are able
to restore the original state of your project using your backup.</p>
<p>Next, follow the installation instructions and do not perform any other backend activities.
Until the successful completion of the Merconis setup, some basic Contao functions may work
incorrectly, as not all relevant settings have been made yet.</p>
<p><a href="%s">Start setup</a></p>
';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-02'] = 'Please run the <a href="%s">Contao Installation Tool</a> now.';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-03'] = 'To set up Merconis, you must first create an API key for security reasons. Please select the menu item "API-Key" in the navigation on the left in the "LS API" section and follow the instructions on this page. Once you have created the API key, click on the "Setup" menu item again.';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-06'] = 'Please choose the theme to use for setup.';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-07'] = 'Install with selected theme';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-08'] = 'Live preview';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-09'] = 'In special cases Merconis can be set up with a downloaded theme, but by default the "Online Theme Repository" is used. If you have problems using the Online Theme Repository and want to use a downloaded theme, please contact techSupport for assistance.';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-10'] = 'This theme is only compatible with Contao %s';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-11'] = 'This theme is only compatible with Merconis %s';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-12'] = 'The theme you selected can not be installed. Please contact the Merconis support in order to fix this issue or choose another theme.';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-13'] = 'Please choose a theme';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-14'] = 'Downloaded themes';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-15'] = 'Online Theme Repository';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-16'] = 'Direct access to the online theme repository isn\'t possible at the moment%s.';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-17'] = 'CURL extension not available';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-18'] = 'No data received';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-19'] = 'Retrieving a theme from the online theme repository usually only takes a few seconds. Depending on the internet connection it may take up to 1 minute.';

$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage07'] = '<h2>Merconis %s has been set up completely!</h2><p>You can take a look at your shop in the front end <a href="%s" target="_blank">HERE</a> .</p><p>You can now start with your individual shop configuration. We recommend you to read the corresponding checklist in the manual.</p>';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage08'] = '<h2>Merconis %s has been set up completely!</h2>';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage09'] = '<h2>Your Contao version is not compatible with this version of Merconis.</h2>';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage10'] = '<p class="shopHint">Important note: The Merconis installer has generated a multilingual page structure. To enable correct processing of multilingualism and correct presentation of Merconis, the page  "Merconis - Root page (main language)" must be marked language fallback. The installer could not mark the language fallback automatically since there might have been a conflict with an already existing root page. Please make the relevant setting manually, if required.</p>';

$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage11'] = '<p>The programm version on hand deviates from the current installation state. This is okay if you have only just loaded the program files of a new Merconis version for an update.</p><p><strong>Your installation state corresponds to version %s<br />Your program version is %s</strong></p><p class="bottomButton"><a href="%s"><button type="button">Start update</button></a></p>';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage12'] = 'Instructions';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage13'] = '<p><strong>The update comprises several version updates which must be worked off separately. Please strictly adhere to the given order.</strong></p>';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage14'] = '<p class="bottomButton"><a href="%s" onclick="return confirm(\'Are you sure?\');"><button type="button">Mark update as completed</button></a></p>';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage15'] = '<h2 class="merconisUpdaterHeadline">Merconis UPDATE HELPER</h2>';

$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage16'] = '<p>Please follow the instructions in the link now. Click <a href="%s">here</a> to call the Contao install tool if the instructions prompt you to do this</p>';

$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage17'] = '<p>Your last installed version could not be clearly identified. Please select the version that you had installed on your system before you loaded the new program files.</p>';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage18'] = 'Run conversion routine';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage19'] = 'Conversion completed';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage20'] = 'Conversion not possible yet';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage21'] = '<span class="updateLessImportantNote">Nothing to do for this update step</span>';

$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage22'] = '<h2 class="merconisInstallerHeadline">Merconis INSTALLER</h2><p class="shopHint">The automatic installation is not possible in your project. There must be either an aborted or incompletely uninstalled Merconis installation or other environmental conditions make the use of the installer impossible.</p><p>Please contact the <strong>Merconis techSupport</strong> in order to solve the problem and/or to get help with setting up Merconis without the automatic installer. You can find the contact information of the <strong>Merconis techSupport</strong> on the Merconis website <a href="http://www.merconis.com" target="_blank">(www.merconis.com)</a>.</p><p><strong>HINT:</strong> You can also see this message in a complete and correctly set up Merconis installation if the entry "ls_shop_installedCompletely" has been removed from your localconfig.php or is not set to "true", which might be the case if you restored an old version of your localconfig.php from a backup.</p>';

$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['updateErrors']['currentVersionSituationInvalid'] = '<p class="shopHint">The identified version numbers for the current installation state and the existing program files are faulty. This error occurs e.g. if the installation state corresponds to a higher version than the version of the currently existing program files. This would mean downgrading your Merconis version which is not supported by the UPDATE HELPER. If you actually want to perform this downgrade, please arrange the necessary changes (settings, modules etc.) as you see fit.</p>';
$GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['updateErrors']['currentFilesVersionUnreleased'] = '<p class="shopHint">The installed program version, which was determined on the basis of the available program files, does not correspond to an official release. This can happen if you have updated to the latest version of the master branch using Composer or Contao Manager. The Merconis Update Helper does not support this, so please contact techSupport if necessary. As soon as you have updated the program version to an official release, the Update Helper is available as usual.</p>';