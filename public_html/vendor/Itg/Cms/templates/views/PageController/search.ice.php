<?php


$search_results = $results->getResults();


?>

<div class="row">

<div class="col-md-12">
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="panel-title">{{ number_format(count($search_results)) }} {{ (count($search_results) == 1) ? 'result' : 'results' }} found for &quot;{{{ $results->getSearchTermsString() }}}&quot;</h4>
                    @if (count($results->getSearchTags()))
                        <p>
                            <select id="search_tag_select" class="form-control input-sm chosen-select" data-placeholder="Filter..." multiple="multiple">
                                @foreach ($results->getSearchTags() as $tag)
                                    <option value="{{{ $tag }}}">{{{ $tag }}}</option>
                                @endforeach
                            </select>
                        </p>
                    @endif
                </div>
                <div class="col-md-6"></div>
            </div>
        </div><!-- panel-heading -->
        <div class="panel-body">

            <div class="results-list">

                @foreach ($search_results as $search_result)
                    <div class="media search-result" data-section-tag="{{{ $search_result->tag }}}">
                        <div class="media-body">
                            <h4 class="media-title">
                                @if ($search_result->url != '')
                                    <a href="{{ $search_result->url }}" target="_blank">
                                @endif
                                {{{ $search_result->header }}}
                                @if ($search_result->url != '')
                                    </a>
                                @endif
                            </h4>
                            @if ($search_result->body != '')
                                <p><small class="text-muted">{{{ $search_result->body }}}</small></p>
                            @endif
                            @if ($search_result->tag != '')
                            <span class="badge">{{ $search_result->tag }}</span>
                                @endif
                        </div>
                    </div>
                @endforeach

                @if (!count($search_results))
                    No search results
                @endif

            </div><!-- results-list -->

        </div><!-- panel-body -->
    </div><!-- panel -->
</div><!-- col-sm-8 -->
</div><!-- row -->