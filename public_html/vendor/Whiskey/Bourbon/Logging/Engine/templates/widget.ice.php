<style> #_whsky_error_widget{position:fixed;z-index:2147483646;top:0;left:0;right:0;padding:15px 15px 0;background-color:rgba(35,35,35,.95);box-shadow:0 0 2px 2px rgba(0,0,0,.5);font-family:'Open Sans',sans-serif;font-size:12px}._whsky_error_widget_level{display:inline-block;font-size:14px;color:#fff;border-radius:2px;padding:5px;margin:0 7px 15px 0}#_whsky_error_widget>details{display:block;padding-bottom:15px;color:#fff;padding-top:15px;border-top:double rgba(255,255,255,.1)}#_whsky_error_widget>details>summary{outline:0;cursor:pointer}#_whsky_error_widget>details>summary>span{font-size:16px;vertical-align:middle}._whsky_error_widget_description{font-size:14px;margin:10px 0 0 15px;font-weight:700;color:rgba(255,255,255,.85)}._whsky_error_widget_details{max-height:175px;overflow:auto}._whsky_error_widget_details>table{margin-left:15px;background-color:transparent}._whsky_error_widget_details>table td{padding-top:10px;background-color:transparent}._whsky_error_widget_details pre{font-family:'Andale Mono',Courier,monospace;font-size:13px;word-break:normal;margin:0;padding:10px;color:rgba(255,255,255,.4);background-color:transparent;border:none}._whsky_error_widget_details_pre_key{font-weight:700}._whsky_error_widget_details_pre_value{white-space:pre-wrap} </style>

<div id="_whsky_error_widget">

    <div>
        @foreach ($log_level_stats as $log_level => $details)
            <span class="_whsky_error_widget_level" style="background-color: {{{ $details['colour'] }}};">{{{ $details['value'] }}}</span>
        @endforeach
    </div>

    @foreach ($errors as $error)

        <details>

            <summary>
                <span><strong>[{{{ $error['additional']['error_code'] }}}]</strong> {{{ html_entity_decode($error['message'], ENT_QUOTES) }}}</span>
            </summary>

            <p class="_whsky_error_widget_description">{{{ $error['additional']['file'] }}} &#40;line {{{ number_format($error['additional']['line_number']) }}}&#41;</p>

            <div class="_whsky_error_widget_details">

                <table>

                    <tr>
                        <td valign="top">
                            <pre class="_whsky_error_widget_details_pre_key">Line&nbsp;{{{ number_format($error['additional']['line_number']) }}}</pre>
                        </td>
                        <td> </td>
                        <td valign="top">
                            <pre class="_whsky_error_widget_details_pre_value">{{{ trim($error['additional']['affected_line']) }}}</pre>
                        </td>
                    </tr>

                    @if (!empty($error['additional']))
                        @foreach ($error['additional'] as $var_name => $var_value)
                            @if (in_array($var_name, $allowed_additional) AND !empty($var_value))
                                <tr>
                                    <td valign="top">
                                        <pre class="_whsky_error_widget_details_pre_key">{{{ html_entity_decode(ucwords($var_name), ENT_QUOTES) }}}</pre>
                                    </td>
                                    <td> </td>
                                    <td valign="top">
                                        <pre class="_whsky_error_widget_details_pre_value">@if ($var_name == 'backtrace'){{{ implode("\n\n", $var_value) }}}@else{{{ trim(html_entity_decode(print_r($var_value, true), ENT_QUOTES)) }}}@endif</pre>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @endif

                </table>

            </div>

        </details>

    @endforeach

</div>