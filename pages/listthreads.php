<?php
$uid = (int)$_GET['id'];

$rUser = Query("select * from {users} where id={0}", $uid);
if(NumRows($rUser))
	$user = Fetch($rUser);
else
	Kill(__("Unknown user ID."));

$title = __("Thread list");

$uname = $user["name"];
if($user["displayname"])
	$uname = $user["displayname"];

MakeCrumbs(array(__("Member list")=>actionLink("memberlist"), $uname => actionLink("profile", $uid), __("List of threads")=>""), $links);

$total = FetchResult("SELECT 
						count(*)
					FROM 
						{threads} t
						LEFT JOIN {forums} f ON f.id=t.forum
					WHERE t.user={0} AND f.minpower <= {1}", $uid, $loguser["powerlevel"]);

$tpp = $loguser['threadsperpage'];
if(isset($_GET['from']))
	$from = (int)$_GET['from'];
else
	$from = 0;

if(!$tpp) $tpp = 50;

$rThreads = Query("	SELECT 
						t.*,
						".($loguserid ? "tr.date readdate," : '')."
						su.(_userfields),
						lu.(_userfields)
					FROM 
						{threads} t
						".($loguserid ? "LEFT JOIN {threadsread} tr ON tr.thread=t.id AND tr.id={4}" : '')."
						LEFT JOIN {users} su ON su.id=t.user
						LEFT JOIN {users} lu ON lu.id=t.lastposter
						LEFT JOIN {forums} f ON f.id=t.forum
					WHERE t.user={0} AND f.minpower <= {1}
					ORDER BY lastpostdate DESC LIMIT {2}, {3}", $uid, $loguser["powerlevel"], $from, $tpp, $loguserid);

$numonpage = NumRows($rThreads);

$pagelinks = PageLinks(actionLink("listthreads", $uid, "from="), $tpp, $from, $total);
		
if($pagelinks)
	echo "<div class=\"smallFonts pages\">".__("Pages:")." ".$pagelinks."</div>";

$ppp = $loguser['postsperpage'];
if(!$ppp) $ppp = 20;

if(NumRows($rThreads))
{	
	$forumList = "";
	$haveStickies = 1;
	$cellClass = 0;
	
	while($thread = Fetch($rThreads))
	{
		$forumList .= listThread($thread, $cellClass, true);
		$cellClass = ($cellClass + 1) % 2;
	}
	
	Write(
"
	<table class=\"outline margin width100\">
		<tr class=\"header1\">
			<th style=\"width: 20px;\">&nbsp;</th>
			<th style=\"width: 16px;\">&nbsp;</th>
			<th style=\"width: 60%;\">".__("Title")."</th>
			<th>".__("Started by")."</th>
			<th>".__("Replies")."</th>
			<th>".__("Views")."</th>
			<th>".__("Last post")."</th>
		</tr>
		{0}
	</table>
",	$forumList);
}
else
	Alert(__("No threads found."), __("Error"));

if($pagelinks)
	Write("<div class=\"smallFonts pages\">".__("Pages:")." {0}</div>", $pagelinks);

