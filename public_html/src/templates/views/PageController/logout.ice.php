<section class="secondary-color">

	<div class="flex-container">

		<div class="title-container title-logout">
			<img src="{{ AppEnv::imageDir() }}logout_icon.png" alt="">
			<h3 class="title">see you <span>soon</span></h3>
		</div>

		<div class="visitors">
			@foreach ($visitors as $key => $group)

			<div class="letter">
				<span class="initial-letter"><?php print_r($key); echo "<br>"; ?></span>
				@foreach ($group as $visitor)
				<div class="visitor" id="{{$visitor->getId()}}">
					{{$visitor->getFirstName()}}&nbsp;{{$visitor->getLastName()}}&nbsp;[{{$visitor->getBadge()}}]
					<a href="#" class="button signout " data-signout="{{$visitor->getId()}}" data-firstname="{{$visitor->getFirstName()}}" role="button">SIGN ME OUT</a>
				</div>
				@endforeach
			</div>
			@endforeach

			<div class="back-container">

				<div class="button">
					<a href="{{$_helper->_link(PageController::class, 'home') }}" role="button"><< Back</a>
				</div>
				
			</div>

		</div>
		<p>&nbsp;</p>
	</div>
	
</section>