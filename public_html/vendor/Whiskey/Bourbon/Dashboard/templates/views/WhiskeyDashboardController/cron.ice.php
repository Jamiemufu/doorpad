<?php


use Whiskey\Bourbon\Dashboard\Controller\WhiskeyDashboardController;


?>

<div id="cron">

    <section>
        <h2>What Is Cron?</h2>
        <p>If running on a *nix server, <strong>Cron</strong> can be used to schedule command-line tasks. <a href="http://en.wikipedia.org/wiki/Cron#Examples" target="_blank">Wikipedia</a> explains it rather well.</p>
        <p>Please note that cron jobs can only be set if the system user that the web server runs as has permission to do so &mdash; if a job cannot be created this is likely the reason why.</p>
    </section>

    <section>
        <h2>Status</h2>
        <p><span class="status {{ $cron_active ? '' : 'in' }}active"></span>{{ $cron_active ? 'Enabled' : 'Disabled' }}</p>
    </section>

    @if ($cron_active)
        <section>
            <h2>Cron Jobs</h2>
            <br />
            <button id="cron_wget_button">Create Job To Request URL</button>
            <br />
            <br />
            <table id="cron_job_list">
                <form action="{{ $_helper->_link(WhiskeyDashboardController::class, 'cron_add') }}" method="POST">
                    <thead>
                    <tr>
                        <th>Periodicity</th>
                        <th>Command</th>
                        <th>Action</th>
                    </tr>
                    <tr>
                        <th>
                            <input type="text" name="minute" placeholder="m" class="cron_period_input" />
                            <input type="text" name="hour" placeholder="h" class="cron_period_input" />
                            <input type="text" name="day" placeholder="d" class="cron_period_input" />
                            <input type="text" name="month" placeholder="mth" class="cron_period_input" />
                            <input type="text" name="day_of_week" placeholder="wk" class="cron_period_input" />
                        </th>
                        <th><input type="text" id="cron_new_command_input" name="command" placeholder="Command..." /></th>
                        <th>
                            <input name="cron_add" type="submit" value="Add" />
                        </th>
                    </tr>
                    </thead>
                </form>
                <tbody>
                    @foreach ($cron_jobs as $cron_job)
                        <tr>
                            <td>
                                {{{ $cron_job->getMinute() }}}
                                {{{ $cron_job->getHour() }}}
                                {{{ $cron_job->getDay() }}}
                                {{{ $cron_job->getMonth() }}}
                                {{{ $cron_job->getDayOfWeek() }}}
                            </td>
                            <td>{{{ $cron_job->getCommand() }}}</td>
                            <td>
                                <form action="{{ $_helper->_link(WhiskeyDashboardController::class, 'cron_delete') }}" method="POST" onSubmit="return confirm('Delete cron job?');">
                                    <input type="hidden" name="cron_delete" value="{{{ $cron_job->getMinute() }}} {{{ $cron_job->getHour() }}} {{{ $cron_job->getDay() }}} {{{ $cron_job->getMonth() }}} {{{ $cron_job->getDayOfWeek() }}} {{{ $cron_job->getCommand() }}}" />
                                    <input type="submit" value="Del" />
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

</div>