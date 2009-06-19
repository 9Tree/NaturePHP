<style type="text/css" media="screen">
	#nphp-debug-title{
		margin:0;
		padding:0;
		position:fixed;
		top:100%;
		left:20px;
		height:14px;
		width:210px;
		margin-top:-26px;
		font-size:12px;
		font-weight:bold;
		font-family:'Lucida grande', Verdana;
		padding:8px 10px;
		cursor:pointer;
		cursor:hand;
		background:url(<?php Path::put('nphp_title_bg.png', __FILE__) ?>) 0 0 no-repeat;
	}
	#nphp-debug-title span{
		margin:0;
		padding:0;
		color:#efefef;
	}
	#nphp-debug-title small{
		margin:0;
		padding:0;
		font-size:9px;
		color:lightgreen;
	}
	#nphp-debug-title .warning_img{
		vertical-align:middle; margin-top:-3px; margin-left:2px;
	}
	#nphp-debug-title .max_arrow{
		float:right;
		margin-top:3px;
	}
	#nphp-debug-report{
		margin:0;
		padding:0;
		position:fixed;
		top:100%;
		left:0;
		border:1px #000 solid;
		height:0px;
		width:100%;
		margin-top:1px;
		padding:10px 0;
		padding-bottom:20px;
		background-color:#eeeeee;
		font-family:'Lucida grande', Verdana;
		font-size:13px;
		overflow:hidden;
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
</style>

<!--NaturePHP Debug Report-->
<!--Report div-->
<script type="text/javascript" charset="utf-8">
	function nphp_toggle_report(){
		if(!this.state) this.state='closed';
		this.header=document.getElementById('nphp-debug-title');
		this.cont=document.getElementById('nphp-debug-report');
		this.cont_min_height=30;	//contents padding-top + padding-bottom
		this.min_top=0;
		this.max_top=300;
		this.step=this.max_top/20;	
		var cur_height=nphp_s(this.cont, 'height')?parseInt(nphp_s(this.cont, 'height')):0;
		var cont_cur_loc=nphp_s(this.cont, 'margin-top')?parseInt(nphp_s(this.cont, 'margin-top')):0;
		var h_cur_loc=nphp_s(this.header, 'margin-top')?parseInt(nphp_s(this.header, 'margin-top')):0;
		if(this.state=='closed'){	//open the report tab
			var px_step=this.min_top;
			
			var interval=setInterval(function(){
				if(px_step>this.max_top) px_step=this.max_top;
				this.cont.style['marginTop']=(cont_cur_loc-px_step)+'px';
				this.header.style['marginTop']=(h_cur_loc-px_step)+'px';
				if(px_step>this.cont_min_height) this.cont.style['height']=(cur_height+px_step-this.cont_min_height)+'px';
				if(px_step==this.max_top){
					clearInterval(interval);
					this.cont.style['overflow']='auto';
				} else px_step=px_step+this.step;
			}, 10);
			
			this.state='open';
		} else {
			var px_step=this.min_top;
			this.cont.style['overflow']='hidden';
			var interval=setInterval(function(){
				if(px_step>this.max_top) px_step=this.max_top;
				this.cont.style['marginTop']=(cont_cur_loc+px_step)+'px';
				this.header.style['marginTop']=(h_cur_loc+px_step)+'px';
				if(px_step>this.cont_min_height) this.cont.style['height']=(cur_height-px_step+this.cont_min_height)+'px';
				if(px_step==this.max_top){
					clearInterval(interval);
				} else px_step=px_step+this.step;
			}, 10);
			this.state='closed';
		}
	}
	function nphp_s(x,prop)
	{
		if (x.currentStyle)
			return x.currentStyle[prop];
		else if (window.getComputedStyle)
			return document.defaultView.getComputedStyle(x,null).getPropertyValue(prop);
	}
	
</script>
<div id="nphp-debug-title" onclick="nphp_toggle_report();">
		
	<span>nphp Log</span> <small>Debug Report</small>
	<?php
	if(Log::has_warnings()):
	?>
	<img class="warning_img" src="<?php Path::put('warning_icon.png', __FILE__) ?>" />
	<?php
	endif;
	?>
	<img class="max_arrow" src="<?php Path::put('max_arrow.png', __FILE__) ?>" />
	
</div>
<div id="nphp-debug-report"> 
	<!--NaturePHP Debug Report-->
	<ol id="nphp-debug-error-list">
		<?php print Log::list_events(); ?>
	</ol>
</div>