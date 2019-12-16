<?php


use Whiskey\Bourbon\Dashboard\Controller\WhiskeyDashboardController;


?>

<div id="main">

    <section>
        <h2>What Is This?</h2>
        <p>These pages display information about the server and allow you to manage certain aspects of the application. The widget that links here is only visible when in the <em>development</em> environment.</p>
    </section>

    <section>
        <h2>Extensions</h2>
        <p>The following extensions are required for all parts of Whiskey to function correctly:</p>
        @foreach ($extensions as $name => $status)
            <p><span class="status {{ $status ? '' : 'in' }}active"></span>{{{ $name }}}</p>
        @endforeach
    </section>

    <section>
        <h2>Databases</h2>
        @if (count($databases))
            @foreach ($databases as $name => $status)
                <p><span class="status {{ $status ? '' : 'in' }}active"></span>{{{ $name }}}</p>
            @endforeach
        @else
            <p>No database connections present</p>
        @endif
    </section>

    <section id="templating_engines">
        <h2>Templating Engines</h2>
        <p>The following templating engines are enabled:</p>
        @if (count($templating_engines))
            @foreach ($templating_engines as $name => $status)
                <p><span class="status {{ $status ? '' : 'in' }}active"></span>{{{ $name }}}</p>
            @endforeach
        @else
            <p>No templating engines present</p>
        @endif
    </section>

    <section>
        <h2>Storage</h2>
        <p>The following storage engines are enabled:</p>
        @if (count($storage_engines))
            @foreach ($storage_engines as $name => $status)
                <p><span class="status {{ $status ? '' : 'in' }}active"></span>{{{ $name }}}</p>
            @endforeach
        @else
            <p>No storage engines present</p>
        @endif
    </section>

    <section id="cache_engines">
        <h2>Caching</h2>
        <p>The following cache engines are enabled:</p>
        @if (count($caching_engines))
            @foreach ($caching_engines as $name => $status)
                <p><span class="status {{ $status ? '' : 'in' }}active"></span>{{{ $name }}}</p>
            @endforeach
        @else
            <p>No cache engines present</p>
        @endif
        @if ($cache_clearable)
            <p>
                <form action="{{ $_helper->_link(WhiskeyDashboardController::class, 'clear_caches') }}" method="POST">
                    <input type="submit" value="Flush Cache" />
                </form>
            </p>
        @endif
    </section>

    <section>
        <h2>E-mail</h2>
        <p>The following e-mail engines are enabled:</p>
        @if (count($email_engines))
            @foreach ($email_engines as $name => $status)
                <p><span class="status {{ $status ? '' : 'in' }}active"></span>{{{ $name }}}</p>
            @endforeach
        @else
            <p>No e-mail engines present</p>
        @endif
    </section>

    <section>
        <h2>Random Sources</h2>
        <p>The following random sources are available:</p>
        @if (count($random_sources))
            @foreach ($random_sources as $name => $status)
                <p><span class="status {{ $status ? '' : 'in' }}active"></span>{{{ $name }}}</p>
            @endforeach
        @else
            <p>No random sources present</p>
        @endif
    </section>

    <section>
        <h2>Environment</h2>
        <p>The following information has been gathered from the server:</p>
        <br />
        <table>
            <tbody>
                @foreach ($environment as $key => $value)
                    <tr>
                        <td>{{{ $key }}}</td>
                        <td>{{{ $value }}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

</div>