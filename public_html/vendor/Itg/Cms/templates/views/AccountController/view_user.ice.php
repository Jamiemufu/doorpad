<div class="row">

    <div class="col-sm-2">
        <img src="{{ $user->getIcon() }}" class="thumbnail img-responsive" alt="" />
    </div><!-- col-sm-3 -->

    <div class="col-sm-10">

        <div class="profile-header">
            <h2 class="profile-name">{{ $user->getUsername() }}</h2>
            <div class="profile-info-icon"><i class="fa fa-user"></i> {{ $user->getRoleName() }}</div>
            @if ($user->getEmail() != '')
                <div class="profile-info-icon"><i class="fa fa-envelope"></i> {{ $user->getEmail() }}</div>
            @endif
            <div class="profile-info-icon"><i class="fa fa-clock-o"></i> Last online: {{ ucwords($user->getLastSeenDate()) }}</div>
        </div><!-- profile-header -->

    </div><!-- col-sm-9 -->

</div><!-- row -->