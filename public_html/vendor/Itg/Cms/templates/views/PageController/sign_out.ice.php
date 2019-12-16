<?php
    use Itg\Cms\Http\Controller\AccountController;
    use Itg\Cms\Http\Controller\AdminController;
    use Itg\Cms\Http\Controller\PageController;
    use Whiskey\Bourbon\App\Facade\Paginate;
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h1 class="panel-title">Sign out all visiors</h1>
        <hr>
        <p>Please click the button below to sign out all users</p>
        <br>
        <div>
            <a href="{{ $_helper->_link(PageController::class, 'signOutAll') }}" class="btn btn-success">Sign out all visitors</a>
        </div>
    </div>
</div>