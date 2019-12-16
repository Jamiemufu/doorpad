<?php


use Whiskey\Bourbon\Dashboard\Controller\WhiskeyDashboardController;


?>

<div id="migrations">

    @if (!is_null($new_migration) OR !is_null($migrations_reset))
        <section>
            <h2>Notification</h2>
            @if (!is_null($new_migration))
                @if ($new_migration)
                    <p>Migration successfully created: <strong>{{{ $migration_dir . $new_migration }}}.php</strong></p>
                @else
                    <p>An unknown error occurred when trying to create the migration</p>
                @endif
            @endif
            @if (!is_null($migrations_reset))
                <p>Migration index successfully reset</p>
            @endif
        </section>
    @endif

    <section>
        <h2>What Are Migrations?</h2>
        <p>A migration is an action that can be used to upgrade or downgrade your application. If you have multiple developers working on an application and one of them makes a change to the database schema, they could create a migration that would action this change, allowing other developers to quickly and easily get up-to-date.</p>
        <p>When you create a new migration a notification will let you know its filename. From there you have only to populate the file&#39;s <strong>up&#40;&#41;</strong> and <strong>down&#40;&#41;</strong> methods with the necessary actions to apply and undo the upgrade, respectively. You can also provide a description in the public <strong>$description</strong> property.</p>
    </section>

    <section>
        <h2>Status</h2>
        <p>
            <span class="status {{ $migrations_enabled ? '' : 'in' }}active"></span>
            {{ $migrations_enabled ? 'Enabled' : 'Disabled' }}
        </p>
        @if (!$migrations_enabled)
            <p>The dashboard requires a database connection to work with migrations.</p>
        @endif
    </section>

    @if ($migrations_enabled)

        <section>
            <h2>Manage Migrations</h2>
            <p><a class="confirm-link" href="{{ $_helper->_link(WhiskeyDashboardController::class, 'create_migration') }}">Create a new migration</a></p>
            <p><a class="confirm-link" href="{{ $_helper->_link(WhiskeyDashboardController::class, 'reset_migrations') }}">Reset migration index</a></p>
        </section>

        <section id="migrations_list">
            <h2>Apply Migrations</h2>
            @if (count($migrations) === 1)
                <p>No migrations currently exist</p>
            @else
                @foreach ($migrations as $migration)
                    <p>
                        <span class="pre">{{ (($latest_migration == $migration->getId()) ? '&raquo; ' : '  ') }}</span>
                        <a class="confirm-link" title="{{{ $migration->getId() ? date('jS F Y, H:i:s', $migration->getId()) : ($migration->getId() ? ($migration->description != '') ? $migration->description : '' : 'Undo all migrations') }}}" href="{{ $_helper->_link(WhiskeyDashboardController::class, 'migrate_to', $migration->getId()) }}">{{{ ($migration->description != '') ? $migration->description : date('jS F Y, H:i:s', $migration->getId()) }}}</a>
                        @if (in_array($migration->getId(), $skipped_migrations))
                            <small><a href="{{ $_helper->_link(WhiskeyDashboardController::class, 'action_migration', $migration->getId()) }}">&#40;skipped migration &mdash; click to apply&#41;</a></small>
                        @endif
                    </p>
                @endforeach
            @endif
        </section>

    @endif

</div>