<?php


use Itg\Buildr\Facade\Me;
use Itg\Cms\Http\Controller\AccountController;


?>

<div class="row">

    <div class="col-sm-3">
        <img src="{{ Me::getIcon() }}" class="thumbnail img-responsive" alt="" />
        <small>This image is the <a href="http://en.gravatar.com/support/what-is-gravatar/" target="_blank">gravatar</a> linked to your e-mail address, {{ Me::getEmail() }}; it represents you visually on the Internet. <a href="http://gravatar.com" target="_blank">Click here</a> to update it.</small>
    </div><!-- col-sm-3 -->

    <div class="col-sm-9">

        <div class="profile-header">
            <h2 class="profile-name">{{ Me::getUsername() }}</h2>
            <div class="profile-info-icon"><i class="fa fa-user"></i> {{ Me::getRoleName() }}</div>
            @if (Me::getEmail() != '')
                <div class="profile-info-icon"><i class="fa fa-envelope"></i> {{ Me::getEmail() }}</div>
            @endif
        </div><!-- profile-header -->

        <form action="{{ $_helper->_link(AccountController::class, 'my_account') }}" method="POST" class="form-horizontal form-bordered">

            <input type="hidden" name="csrf_token" value="{csrf_token}" />

            <div class="panel panel-default">

                <div class="panel-heading">

                    <h3 class="panel-title">Update Details</h3>
                    <p>Update your account details</p>

                </div>

                <div class="panel-body panel-body-nopadding">

                    <div class="form-group">
                        <label class="col-sm-3 control-label">E-mail</label>
                        <div class="col-sm-9">
                            <input type="email" name="email" class="form-control" required="required" placeholder="E-mail" value="{{ $email or Me::getEmail() }}" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">
                            Password
                            <br />
                            <small>&#40;Leave blank to not change&#41;</small>
                        </label>
                        <div class="col-sm-9">
                            <input type="password" name="password" class="form-control" placeholder="Password" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">
                            Repeat Password
                        </label>
                        <div class="col-sm-9">
                            <input type="password" name="password_2" class="form-control" placeholder="Repeat password" />
                        </div>
                    </div>

                </div>

                <div class="panel-footer">
                    <button class="btn btn-success pull-right">Update</button>
                </div>

            </div>

        </form>

    </div><!-- col-sm-9 -->

</div><!-- row -->