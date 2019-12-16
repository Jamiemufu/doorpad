<?php


use Itg\Cms\Http\Controller\PageController;


?>

<form action="{{ $_helper->_link(PageController::class, 'downloadReports') }}" method="POST" class="form-horizontal form-bordered">

    <!-- <input type="hidden" name="csrf_token" value="{csrf_token}" /> -->

    <div class="panel panel-default">

        <div class="panel-heading">

            <h3 class="panel-title">Reports</h3>
            <p>Please select start and end date for reports</p>

        </div>

        <div class="panel-body panel-body-nopadding">

            <div class="form-group">
                <label class="col-sm-2 control-label">Start Date:</label>
                <div class="col-sm-5">
                    <input type="date" name="start_date" class="form-control" required="required" placeholder="Start Date" value="" />                    
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">End Date:</label>
                <div class="col-sm-5">
                    <input type="date" name="end_date" class="form-control" required="required" placeholder="End Date" value="" />
                </div>
            </div>

        </div>

        <div class="panel-footer">
            <button class="btn btn-success pull-right">Download Report</button>
        </div>

    </div>

</form>