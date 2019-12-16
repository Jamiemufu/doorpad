<?php


use Itg\Cms\Http\Controller\AccountController;
use Itg\Cms\Http\Controller\AdminController;
use Whiskey\Bourbon\App\Facade\Paginate;


?>

<div class="panel panel-default">

    <div class="panel-heading">

        <h3 class="panel-title">Users</h3>
        <p>Create and edit user accounts</p>

        <div class="panel-heading-buttons">
            <a href="{{ $_helper->_link(AdminController::class, 'create_user') }}" class="btn btn-success">Create</a>
        </div>

    </div>

    <div class="panel-body no-padding">

        <table class="table no-margin">

            <thead>
                <tr>
                    <th></th>
                    <th>Username</th>
                    <th class="hidden-sm hidden-xs">E-mail</th>
                    <th class="hidden-xs">Role</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <img src="{{ $user->icon }}" alt="" class="user-icon-small" />
                        </td>
                        <td>{{ $user->username }}</td>
                        <td class="hidden-sm hidden-xs">{{ $user->email }}</td>
                        <td class="hidden-xs">{{ $user->role }}</td>
                        <td class="actions-cell">

                            <a href="{{ $_helper->_link(AccountController::class, 'view_user', $user->id) }}" data-toggle="tooltip" class="btn btn-default btn-xs tooltips" data-original-title="View">
                                <i class="fa fa-eye"></i>
                            </a>

                            <a href="{{ $_helper->_link(AdminController::class, 'edit_user', $user->id) }}" data-toggle="tooltip" class="btn btn-default btn-xs tooltips" data-original-title="Edit">
                                <i class="fa fa-edit"></i>
                            </a>

                            <a href="{{ $_helper->_link(AdminController::class, 'delete_user', $user->id) }}?csrf_token={csrf_token}" data-toggle="tooltip" class="btn btn-default btn-xs tooltips hidden-xs confirm-link" data-original-title="Delete">
                                <i class="fa fa-trash-o"></i>
                            </a>

                        </td>
                    </tr>
                @endforeach
                @if (!count($users))
                    <tr>
                        <td colspan="5">
                            No other users found
                        </td>
                </tr>
                @endif
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="5">
                        {{ Paginate::getPageLinks() }}
                    </td>
                </tr>
            </tfoot>

        </table>


    </div>

</div>