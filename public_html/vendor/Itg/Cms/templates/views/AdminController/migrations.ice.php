<?php


use Itg\Cms\Http\Controller\AdminController;


$direction = 'up';


?>

<div class="panel panel-default">

    <div class="panel-heading">

        <h3 class="panel-title">Migrations</h3>
        <p>Migrate the application up or down &mdash; please note that migrating down can result in <strong>data loss</strong>, so please be certain that you know what you are doing</p>

    </div>

    <div class="panel-body no-padding">

        <table class="table no-margin">

            <thead>
                <tr>
                    <th></th>
                    <th class="hidden-sm hidden-xs">Date</th>
                    <th width="100%">Description</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>

                @if (count($migrations) === 1)
                    <tr>
                        <td colspan="3">No migrations currently exist</td>
                    </tr>
                @else
                    @foreach ($migrations as $migration)

                        <?php
                            $is_current = ($latest_migration == $migration->getId());
                            if ($is_current)
                            {
                                $direction = 'down';
                            }
                        ?>

                        <tr>
                            <td>{{ $is_current ? '<i class="fa fa-arrow-right"></i>' : '' }}</td>
                            <td class="one-liner hidden-sm hidden-xs">{{ $migration->getId() ? date('jS F Y, H:i', $migration->getId()) : 'Origin' }}</td>
                            <td width="100%">{{{ $migration->getId() ? $migration->description : 'Undo all migrations' }}}</td>
                            <td class="actions-cell">
                                @if (!$is_current)
                                    <a href="{{ $_helper->_link(AdminController::class, 'migrate_to', $migration->getId()) }}?csrf_token={csrf_token}" data-toggle="tooltip" class="btn btn-default btn-xs tooltips confirm-link" data-original-title="Migrate {{ ucwords($direction) }}">
                                        <i class="fa fa-level-{{ $direction }}"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>

                    @endforeach
                @endif

            </tbody>

        </table>


    </div>

</div>