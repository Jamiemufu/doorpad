<?php


use Itg\Cms\Http\Controller\AdminController;


?>

<form action="{{ $_helper->_link(AdminController::class, 'create_user') }}" method="POST" class="form-horizontal form-bordered">

    <input type="hidden" name="csrf_token" value="{csrf_token}" />

    <div class="panel panel-default">

        <div class="panel-heading">

            <h3 class="panel-title">Create User</h3>
            <p>Create a new user account</p>

        </div>

        <div class="panel-body panel-body-nopadding">

            <div class="form-group">
                <label class="col-sm-2 control-label">Username</label>
                <div class="col-sm-5">
                    <input type="text" name="username" class="form-control" required="required" autofocus="autofocus" placeholder="Username" value="{{{ $username or '' }}}" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">E-mail</label>
                <div class="col-sm-5">
                    <input type="email" name="email" class="form-control" required="required" placeholder="E-mail" value="{{{ $email or '' }}}" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Password</label>
                <div class="col-sm-5">
                    <input type="password" name="password" class="form-control" required="required" placeholder="Password" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Role</label>
                <div class="col-sm-5">
                    {{ $_helper->_postForm()->select($user_roles, array('name' => 'role', 'class' => 'chosen-select form-control input-sm', 'required' => 'required'), (isset($role) ? $role : ''))->element() }}
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Parent User</label>
                <div class="col-sm-5">
                    {{ $_helper->_postForm()->select($users, array('name' => 'parent_id', 'class' => 'chosen-select form-control input-sm', 'required' => 'required'), (isset($parent_id) ? $parent_id : ''))->element() }}
                </div>
            </div>

        </div>

        <div class="panel-footer">
            <button class="btn btn-success pull-right">Create</button>
        </div>

    </div>

</form>