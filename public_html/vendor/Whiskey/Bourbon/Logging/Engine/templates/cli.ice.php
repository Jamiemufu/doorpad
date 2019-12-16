
-----

Errors:@foreach ($log_level_stats as $log_level => $details)
{{ "\033[" . $details['colour'] . "m" }} {{ $details['value'] . "\033[0m" }}
@endforeach


@foreach ($errors as $error)
-----

[{{ $error['additional']['error_code'] }}] {{ html_entity_decode($error['message'], ENT_QUOTES) }} 
{{ $error['additional']['file'] }} (line {{ number_format($error['additional']['line_number']) }})

@endforeach
-----

