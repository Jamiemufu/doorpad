<script type="text/javascript">
var iWebkit;if(!iWebkit){iWebkit=window.onload=function(){function fullscreen(){var a=document.getElementsByTagName("a");for(var i=0;i<a.length;i++){if(a[i].className.match("noeffect")){}else{a[i].onclick=function(){window.location=this.getAttribute("href");return false}}}}function hideURLbar(){window.scrollTo(0,0.9)}iWebkit.init=function(){fullscreen();hideURLbar()};iWebkit.init()}}
</script>

<section class="secondary-color">

<div class="flex-container">

	<div class="title-container">
		<img src="{{ AppEnv::imageDir() }}login_icon.png" alt="">
		<h3 class="title">pop in <span>your details…</span></h3>
	</div>

	<!-- <form class="login" method="POST" action="login"> -->
	<form class="login">
		<span class="input input-round">

			<label for="firstname">
				<span class="input-label">First Name*</span>
			</label>

			<input type="text" name="firstname" id="firstname" size="30" autocorrect="off" autocapitalize="none" autocomplete="off" spellcheck="false" >

		</span>
		<br/>

		<span class="input input-round">

			<label for="lastname">
				<span class="input-label">Last Name*</span>
			</label>

			<input type="text" name="lastname" id="lastname" size="30" autocorrect="off" autocapitalize="none" autocomplete="off" spellcheck="false" >

		</span>
		<br/>

		<span class="input input-round">

			<label for="badge">
				<span class="input-label">Badge Number*</span>
			</label>

			<input type="number" name="badge" id="badge" size="30" autocorrect="off" autocapitalize="none" autocomplete="off" spellcheck="false">

		</span>
		<br/>
		
		<span class="input input-round">

			<label for="carReg">
				<span class="input-label">Car Registration* <br> <!-- <span class="input-reg--description">(for token validation)</span> --></span>
			</label>

			<input class="input-reg" type="text" name="carReg" id="carReg" size="30" autocorrect="off" autocomplete="off" spellcheck="false" placeholder="For parking token validation">

		</span>

		<br>

		<span class="input input-round">

			<label for="company">
				<span class="input-label">Company</span>
			</label>

			<input type="text" name="company" id="company" size="30" autocorrect="off" autocapitalize="none" autocomplete="off" spellcheck="false">

		</span>
		<br/>

		<span class="input input-round">

			<label for="visiting">
				<span class="input-label">Visiting</span>
			</label>

			<input type="text" name="visiting" id="visiting" size="30" autocorrect="off" autocapitalize="none" autocomplete="off" spellcheck="false">

		</span>
		<br/>
		
		<div class="sign-container">

			<h3>and you’re in</h3>
			<input name="csrf_token" type="hidden" value="{csrf_token}" />

			<div class="button">
				<a href="#" id="login" role="button">Sign Me In</a>
			</div>

		</div>

		<div class="back-container">

			<div class="button">
				<a href="{{$_helper->_link(PageController::class, 'home') }}" role="button"><< Back</a>
			</div>

		</div>

	</form>
</div>


</section>