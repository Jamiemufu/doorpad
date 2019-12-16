<?php


use Itg\Cms\Http\Controller\AdminController;


?>

<div class="panel panel-default">

    <div class="panel-heading">

        <h3 class="panel-title">Full Site Backups</h3>
        <p>Create and manage full site gzipped tarball backups</p>

        <div class="panel-heading-buttons">
            <a href="{{ $_helper->_link(AdminController::class, 'backups_site', 'create') }}" class="btn btn-success">Create</a>
        </div>

    </div>

    <div class="panel-body no-padding">

        <table class="table no-margin">

            <thead>
                <tr>
                    <th>Date</th>
                    <th class="hidden-sm hidden-xs">Size</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($backups as $backup)
                <tr>
                    <td>{{ $backup->friendly_time }}</td>
                    <td class="hidden-sm hidden-xs">{{ $backup->friendly_size }}</td>
                    <td class="actions-cell">

                        <a href="{{ $_helper->_link(AdminController::class, 'backups_site', 'download', $backup->time) }}" target="_blank" data-toggle="tooltip" class="btn btn-default btn-xs tooltips" data-original-title="Download">
                            <i class="fa fa-download"></i>
                        </a>

                        <a href="{{ $_helper->_link(AdminController::class, 'backups_site', 'delete', $backup->time) }}?csrf_token={csrf_token}" data-toggle="tooltip" class="btn btn-default btn-xs tooltips confirm-link" data-original-title="Delete">
                            <i class="fa fa-trash-o"></i>
                        </a>

                    </td>
                </tr>
                @endforeach
                @if (!count($backups))
                <tr>
                    <td colspan="3">No backups found</td>
                </tr>
                @endif
            </tbody>

        </table>


    </div>

</div>