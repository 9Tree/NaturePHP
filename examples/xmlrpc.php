<?php
include('../nphp/init.php');



//post
$title = "my blog post title";
$keywords = "";
$categories = array("Test");
$body = "This is just a quick <a href=\"http://google.com\">test</a>";

$host = "mydomain.com";
$path = "/blog/xmlrpc.php";
$username = "admin";
$password = "mypwd";
$encoding='UTF-8';





$title = htmlentities($title,ENT_NOQUOTES,$encoding);
$keywords = htmlentities($keywords,ENT_NOQUOTES,$encoding);

$content = array(
    'title' => $title,
    'description' => $body,
    'mt_allow_comments' => 0, # 1 to allow comments
    'mt_allow_pings' => 0, # 1 to allow trackbacks
    'post_type' => 'post',
    'mt_keywords' => $keywords,
    'categories' => $categories
);

$params = array(0,$username,$password,$content,true);

$rpc = new Xmlrpc($host, $path);
$rpc->setCredentials($username, $password);
$rpc->setDebug(true);
var_dump($rpc->call('metaWeblog.newPost', $params));

?>