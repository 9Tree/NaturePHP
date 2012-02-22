<style type="text/css" media="screen">
#nphp-debug-overlay{
	position:absolute;
	width:100%;
	left:0;
	top:0;
	height:100%;
	background-color:#000;
	opacity:0.6;
}
#nphp-fatalerror-wrapper{
	position:absolute;
	width:1000px;
	margin-left:-500px;
	left:50%;
	top:50px;
	height:auto;
	border:medium dashed #ccc;
}
#nphp-debug-report{
	width:90%;
	margin:15px auto;
	height:auto;
	padding:10px;
	padding-bottom:20px;
	background-color:#eeeeee;
	font-family:'Lucida grande', Verdana;
	font-size:13px;
	border:6px #bbb solid;
}
#nphp-debug-report ol{
	margin:0 40px;
	padding:0;
	list-style-type:decimal !important;
}
#nphp-debug-report ol li{
	margin:0;
	padding:6px;
	height:auto;
}
#nphp-debug-report span.NPHP_warning{
	background:#cc0000;
	color:#fff;
	padding:2px;
	font-weight:normal;
	font-size:13px;
	line-height:21px;
}
#nphp-debug-report span.NPHP_warning a{
	color:lightgreen;
	text-decoration:none;
}
#nphp-debug-report span.NPHP_warning a:hover{
	background:#fff;
	color:#333;
}
#nphp-debug-report span.NPHP_info{
	color:#33729e;
}
#nphp-debug-report span.NPHP_default{

}
#nphp-debug-report h1.nphp-debug-title{
	color:#000;
	padding:0;
	margin:0;
	font-size:2.3em;
	font-weight:normal;
	font-family: "Lucida Grande";
}		
#nphp-debug-report small.nphp-debug-subtitle{
	padding:0;
	color:red;
	margin-left:3px;
	font-weight:normal;
	font-family: Georgia;
	font-style:italic;
	font-size:1em;
}
</style>

<!--Escaping possible html code-->
">"></script></a></option></select></li></ul></ol>
<!--Report div-->
<div id="nphp-debug-overlay"></div>
<div id="nphp-fatalerror-wrapper" style="width:1000px;margin-left:-500px;">
	<div id="nphp-debug-report"> 
		<h1 class="nphp-debug-title">Oops! We seem to have a problem here.</h1>
		<small class="nphp-debug-subtitle">Debug Report</small><br /><br />
		<!--NaturePHP Debug Report-->

		<ol><?php print Log::list_events(); ?><li><span class="NPHP_fatal-error"><strong>Fatal Error</strong> :: <?php print $FATAL_ERROR; ?></span></li></ol>
	</div>
</div>