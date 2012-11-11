<?php

function getRefreshActionLink()
{
	$args = "ajax=1";

	if(isset($_GET["from"]))
		$args .= "&from=".$_GET["from"];

	return actionLink((isset($_GET["page"]) ? $_GET['page'] : 0), (isset($_GET['id']) ? $_GET["id"] : 0), $args);
}

function printRefreshCode()
{
	if(Settings::get("ajax"))
		write(
	"
		<script type=\"text/javascript\">
			refreshUrl = ".json_encode(getRefreshActionLink()).";
			window.addEventListener(\"load\",  startPageUpdate, false);
		</script>
	");
}

function urlNamify($urlname)
{
	$urlname = strtolower($urlname);
	$urlname = str_replace("&", "and", $urlname);
	$urlname = preg_replace("/[^a-zA-Z0-9]/", "-", $urlname);
	$urlname = preg_replace("/-+/", "-", $urlname);
	$urlname = preg_replace("/^-/", "", $urlname);
	$urlname = preg_replace("/-$/", "", $urlname);
	return $urlname;
}

$urlNameCache = array();
function setUrlName($action, $id, $urlname)
{
	global $urlNameCache;
	$urlNameCache[$action."_".$id] = $urlname;
}

if($urlRewriting)
	include("urlrewriting.php");
else
{

	function actionLink($action, $id="", $args="", $urlname="")
	{
		global $boardroot, $mainPage;
		if($boardroot == "")
			$boardroot = "./";

		$bucket = "linkMangler"; include('lib/pluginloader.php');

		$res = "";

		if($action != $mainPage)
			$res .= "&page=$action";

		if($id != "")
			$res .= "&id=".urlencode($id);
		if($args)
			$res .= "&$args";

		if(strpos($res, "&amp"))
		{
			debug_print_backtrace();
			Kill("Found &amp;amp; in link");
		}

		if($res == "")
			return $boardroot;
		else
			return $boardroot."?".substr($res, 1);
	}
}

function actionLinkTag($text, $action, $id=0, $args="", $urlname="")
{
	return '<a href="'.htmlentities(actionLink($action, $id, $args, $urlname)).'">'.$text.'</a>';
}
function actionLinkTagItem($text, $action, $id=0, $args="", $urlname="")
{
	return '<li><a href="'.htmlentities(actionLink($action, $id, $args, $urlname)).'">'.$text.'</a></li>';
}

function actionLinkTagConfirm($text, $prompt, $action, $id=0, $args="")
{
	return '<a onclick="return confirm(\''.$prompt.'\'); " href="'.htmlentities(actionLink($action, $id, $args)).'">'.$text.'</a>';
}
function actionLinkTagItemConfirm($text, $prompt, $action, $id=0, $args="")
{
	return '<li><a onclick="return confirm(\''.$prompt.'\'); " href="'.htmlentities(actionLink($action, $id, $args)).'">'.$text.'</a></li>';
}

function resourceLink($what)
{
	global $boardroot;
	return "$boardroot$what";
}

function themeResourceLink($what)
{
	global $theme, $boardroot;
	return $boardroot."themes/$theme/$what";
}

function getMinipicTag($user)
{
	global $dataUrl;
	$minipic = "";
	if($user["minipic"] == "#INTERNAL#")
		$minipic = "<img src=\"${dataUrl}minipics/${user["id"]}\" alt=\"\" class=\"minipic\" />&nbsp;";
	else if($user["minipic"])
		$minipic = "<img src=\"".$user['minipic']."\" alt=\"\" class=\"minipic\" />&nbsp;";
	return $minipic;
}

$powerlevels = array(-1 => " [".__("banned")."]", 0 => "", 1 => " [".__("local mod")."]", 2 => " [".__("full mod")."]", 3 => " [".__("admin")."]", 4 => " [".__("root")."]", 5 => " [".__("system")."]");

function userLink($user, $showMinipic = false, $customID = false)
{
	global $powerlevels;

	$bucket = "userMangler"; include("./lib/pluginloader.php");

	$fpow = $user['powerlevel'];
	$fsex = $user['sex'];
	$fname = ($user['displayname'] ? $user['displayname'] : $user['name']);
	$fname = htmlspecialchars($fname);
	$fname = str_replace(" ", "&nbsp;", $fname);

	$minipic = "";
	if($showMinipic || Settings::get("alwaysMinipic"))
		$minipic = getMinipicTag($user);
	{
	}

	$fname = $minipic.$fname;
	
	if(!Settings::get("showGender"))
		$fsex = 2;
	
	if($fpow < 0) $fpow = -1;
	$classing = " class=\"nc" . $fsex . (($fpow < 0) ? "x" : $fpow)."\"";

	if ($customID)
		$classing .= " id=\"$customID\"";

/*
	if($hacks['alwayssamepower'])
		$fpow = $hacks['alwayssamepower'] - 1;
	if($hacks['alwayssamesex'])
		$fsex = $hacks['alwayssamesex'];

	if($hacks['themenames'] == 1)
	{
		global $lastJokeNameColor;
		$classing = " style=\"color: ";
		if($lastJokeNameColor % 2 == 1)
			$classing .= "#E16D6D; \"";
		else
			$classing .= "#44D04B; \"";
		if($fpow == -1)
			$classing = " class=\"nc0x\"";
		$lastJokeNameColor++;
	} else if($hacks['themenames'] == 2 && $fpow > -1)
	{
		$classing = " style =\"color: #".GetRainbowColor()."\"";
	} else if($hacks['themenames'] == 3)
	{
		if($fpow > 2)
		{
			$fname = "Administration";
			$classing = " class=\"nc23\"";
		} else if($fpow == -1)
		{
			$fname = "Idiot";
			$classing = " class=\"nc2x\"";
		} else
		{
			$fname = "Anonymous";
			$classing = " class=\"nc22\"";
		}
	}
	*/

	$bucket = "userLink"; include('lib/pluginloader.php');
	$title = htmlspecialchars($user['name']) . " (".$user["id"].") ".$powerlevels[$user['powerlevel']];
	$userlink = actionLinkTag("<span$classing title=\"$title\">$fname</span>", "profile", $user["id"], "", $user["name"]);
	return $userlink;
}

function userLinkById($id)
{
	global $userlinkCache;

	if(!isset($userlinkCache[$id]))
	{
		$rUser = Query("SELECT u.(_userfields) FROM {users} u WHERE u.id={0}", $id);
		if(NumRows($rUser))
			$userlinkCache[$id] = getDataPrefix(Fetch($rUser), "u_");
		else
			$userlinkCache[$id] = array('id' => 0, 'name' => "Unknown User", 'sex' => 0, 'powerlevel' => -1);
	}
	return UserLink($userlinkCache[$id]);
}

function makeThreadLink($thread)
{
	$tags = ParseThreadTags($thread["title"]);

	$link = actionLinkTag($tags[0], "thread", $thread["id"], "", $tags[0]);
	$tags = $tags[1];

	if (Settings::get("tagsDirection") === 'Left')
		return $tags." ".$link;
	else
		return $link." ".$tags;

}

function pageLinks($url, $epp, $from, $total)
{
	$url = htmlspecialchars($url);

	$numPages = ceil($total / $epp);
	$page = ceil($from / $epp) + 1;

	$first = ($from > 0) ? "<a class=\"pagelink\" href=\"".$url."0\">&#x00AB;</a> " : "";
	$prev = $from - $epp;
	if($prev < 0) $prev = 0;
	$prev = ($from > 0) ? "<a class=\"pagelink\"  href=\"".$url.$prev."\">&#x2039;</a> " : "";
	$next = $from + $epp;
	$last = ($numPages * $epp) - $epp;
	if($next > $last) $next = $last;
	$next = ($from < $total - $epp) ? " <a class=\"pagelink\"  href=\"".$url.$next."\">&#x203A;</a>" : "";
	$last = ($from < $total - $epp) ? " <a class=\"pagelink\"  href=\"".$url.$last."\">&#x00BB;</a>" : "";

	$pageLinks = array();
	for($p = $page - 5; $p < $page + 10; $p++)
	{
		if($p < 1 || $p > $numPages)
			continue;
		if($p == $page || ($from == 0 && $p == 1))
			$pageLinks[] = "<span class=\"pagelink\">$p</span>";
		else
			$pageLinks[] = "<a class=\"pagelink\"  href=\"".$url.(($p-1) * $epp)."\">".$p."</a>";
	}

	return $first.$prev.join(array_slice($pageLinks, 0, 11), "").$next.$last;
}

function absoluteActionLink($action, $id=0, $args="")
{
    return ($https?"https":"http") . "://" . $_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']).substr(actionLink($action, $id, $args), 1);
}

function getRequestedURL()
{
    return $_SERVER['REQUEST_URI'];
}

function getServerURL($https = false)
{
    return ($https?"https":"http") . "://" . $_SERVER['SERVER_NAME'] . "/";
}

function getFullRequestedURL($https = false)
{
    return getServerURL($https) . $_SERVER['REQUEST_URI'];
}

function getFullURL()
{
	return getFullRequestedURL();
}

?>
