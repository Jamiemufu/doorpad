<?php

namespace Itg\Cms\Http\Model;


use Exception;
use Whiskey\Bourbon\App\Facade\Session;
use Whiskey\Bourbon\App\Http\MainModel;
use Whiskey\Bourbon\App\Facade\Utils;


/**
 * MainModel class
 * @package Whiskey\Bourbon\App\Http\Model
 */
class PageModel extends MainModel
{

    /**
     * Download CSV
     * @param $data - array of visitors
     */
    public function downloadCSV($data)
    {       
        $csv = Utils::arrayToCsv($data);
        $filename = 'downloadReport-' . date('Y-m-d') . '.csv';

        header('Content-Type: application/csv');
        header("Content-Disposition: attachment; filename=$filename");        
        echo $csv;

        exit();
    }

}