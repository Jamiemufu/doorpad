<?php


use Itg\Cms\Http\Controller\AdminController;


?>

<form action="{{ $_helper->_link(AdminController::class, 'edit_user', $user->getId()) }}" method="POST" class="form-horizontal form-bordered">

    <input type="hidden" name="csrf_token" value="{csrf_token}" />

    <div class="panel panel-default">

        <div class="panel-heading">

            <h3 class="panel-title">Edit User &#39;{{ $user->getUsername() }}&#39;</h3>
            <p>Update user account</p>

        </div>

        <div class="panel-body panel-body-nopadding">

            <div class="form-group">
                <label class="col-sm-2 control-label">E-mail</label>
                <div class="col-sm-5">
                    <input type="email" name="email" class="form-control" required="required" placeholder="E-mail" value="{{ $email or $user->getEmail() }}" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">
                    Password
                    <br />
                    <small>&#40;Leave blank to not change&#41;</small>
                </label>
                <div class="col-sm-5">
                    <input type="password" name="password" class="form-control" placeholder="Password" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Role</label>
                <div class="col-sm-5">
                    {{ $_helper->_postForm()->select($user_roles, array('name' => 'role', 'class' => 'chosen-select form-control input-sm', 'required' => 'required'), (isset($role) ? $role : $user->getRole()))->element() }}
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Parent User</label>
                <div class="col-sm-5">
                    {{ $_helper->_postForm()->select($users, array('name' => 'parent_id', 'class' => 'chosen-select form-control input-sm', 'required' => 'required'), (isset($parent_id) ? $parent_id : $user->getParentId()))->element() }}
                </div>
            </div>

        </div>

        <div class="panel-footer">
            <button class="btn btn-success pull-right">Update</button>
        </div>

    </div>

</form>