<?php

require_once(dirname(__FILE__) . '/lib/OAuth.php');
$oAuth = new OAuth();

require_once(dirname(__FILE__) . '/lib/Graph.php');
$graph = new Graph($oAuth);

$photo = $graph->getPhoto();
$profile = $graph->getProfile();

echo '<h1>Hello there, ' . $profile->displayName . '</h1>';

echo '<h2><a href="' . _URL . '?_oAuth-action=logout">Log out</a></h2>';

echo 'Your roles in this app are:<ul>';
foreach ($oAuth->getUserRoles() as $role) {
    echo '<li>' . $role . '</li>';
}
echo '</ul>';

echo '<p>' . $photo . '</p>';

echo '<p>Profile Graph API output:</p>';
echo '<pre>';
print_r($profile);
echo '</pre>';

?>