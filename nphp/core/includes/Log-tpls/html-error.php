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
	width:600px;
	margin-left:-300px;
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
#nphp-debug-report h1.nphp-debug-title{
	color:#000;
	padding:0;
	margin:0;
	font-size:2.3em;
	font-weight:normal;
	font-family: "Lucida Grande";
}		
</style>

<!--Escaping possible html code-->
">"></script></a></option></select></li></ul></ol>
<!--Report div-->
<div id="nphp-debug-overlay"></div>
<div id="nphp-fatalerror-wrapper">
	<div id="nphp-debug-report"> 
		<h1 class="nphp-debug-title">Oops!<br /> We seem to have a problem here.</h1>
		<br /><br />There seems to be a fatal application error on this page.<br />
		You may try to <a href="javascript:window.location.reload()">reload the page</a> or report this error to a website administrator.<br /><br />
		We're sorry for the inconvenience.
	</div>
</div>