<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Jobs\TransactionJob;
use Exception;
use Illuminate\Support\Facades\Bus;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function import(Request $request)
    {
        if (auth()->user()) {
            if (count($request->files)) {
                foreach ($request->files as $file) {
                    $validator = Validator::make(['extension' =>  strtolower($file->getClientOriginalExtension()), 'size' => $file->getSize(), 'error' => $file->getError()], [
                        'extension' => 'in:csv,xlsx,xls',
                        'size' => 'max:512000',
                        'error' => 'in:0'
                    ]);

                    if(!$validator->fails()){
                        $this->transaction($file->getPathName(), strtolower($file->getClientOriginalExtension()));
                    }
                }
            } else {
                return response()->json(['error' => 'Please input a valid files']);
            } 
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function transaction($path, $extension = 'csv')
    {
        if (auth()->user()) {
            if (File::exists($path)) {
                ini_set('max_execution_time', 0);
                $extension = $extension ?? strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($extension, ['xls', 'xlsx'])) {
                    $batch = Bus::batch([])->dispatch();

                    $extension = IOFactory::identify($path);
                    $reader = IOFactory::createReader($extension);
                    $reader->setReadDataOnly(true);
                    $reader->setReadEmptyCells(false);

                    $colums   = ['A', 'B', 'C', 'D'];
                    $chunkReader = new ChunkReadFilter($colums);

                    $reader->setReadFilter($chunkReader);

                    $chunkSize = 10000;
                    $reader->setReadFilter($chunkReader);

                    $j = 0;
                    for ($startRow = 1;; $startRow += $chunkSize) {
                        $chunkReader->setRows($startRow, $chunkSize);

                        $spreadsheet = $reader->load($path);

                        $spreadsheet->setActiveSheetIndex(0);

                        if ($spreadsheet->getActiveSheet()->getHighestRow() == 1) {
                            break;
                        }

                        $activeRange = $spreadsheet->getActiveSheet()->calculateWorksheetDataDimension();
                        $activeRange = str_replace('A1', 'A' . $startRow, $activeRange);
                        $sheetData   = $spreadsheet->getActiveSheet()->rangeToArray($activeRange, null, true, true, true);

                        $i = 1;
                        $data_chunk = [];
                        foreach ($sheetData as $row) {
                            $j++;
                            if ($i <= 50 && $j > 1) {
                                try {
                                    $date = "";
                                    if (is_numeric($row['A'])) {
                                        $date = Date::excelToDateTimeObject($row['A'])->format('Y-m-d H:i:s');
                                    } else {
                                        $date = Carbon::createFromFormat('d/m/Y H:i:s', $row['A'])->format('Y-m-d H:i:s');
                                    }
                                    $chunk = [
                                        'date' => $date,
                                        'content' => $row['B'],
                                        'amount' => $row['C'],
                                        'type' => $row['D'],
                                    ];
                                } catch (Exception $ex) {
                                    Log::error("Excel Error Row: {$j}. Message: " . $ex->getMessage());
                                    continue;
                                }

                                $validator = Validator::make($chunk, [
                                    'date' => 'required|date_format:Y-m-d H:i:s',
                                    'content' => 'max:500',
                                    'amount' => 'required|integer',
                                    'type' => 'required|in:Deposit,Withdraw',
                                ]);

                                if (!$validator->fails()) {
                                    $data_chunk[] = $chunk;
                                }
                            }

                            if ($i == 50) {
                                $batch->add([new TransactionJob($data_chunk)]);
                                $data_chunk = [];
                                $i = 1;
                            } else {
                                $i++;
                            }
                        }

                        if (!empty($data_chunk)) {
                            $batch->add([new TransactionJob($data_chunk)]);
                        }

                        $spreadsheet->disconnectWorksheets();
                        unset($spreadsheet);
                    }
                } else if ($extension == 'csv') {
                    $handle = fopen($path, 'r');

                    $batch = Bus::batch([])->dispatch();
                    fgetcsv($handle, 0, ",");

                    $i = 1;
                    $data_chunk = [];
                    $j = 1;
                    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                        if ($i <= 50) {
                            try {
                                $chunk = [
                                    'date' => Carbon::createFromFormat('d/m/Y H:i:s', $data[0])->format('Y-m-d H:i:s'),
                                    'content' => $data[1],
                                    'amount' => $data[2],
                                    'type' => $data[3],
                                ];
                            } catch (Exception $ex) {
                                Log::error("Excel Error Row: {$j}. Message: " . $ex->getMessage());
                                continue;
                            }

                            $validator = Validator::make($chunk, [
                                'date' => 'required|date_format:Y-m-d H:i:s',
                                'content' => 'max:500',
                                'amount' => 'required|integer',
                                'type' => 'required|in:Deposit,Withdraw',
                            ]);

                            if (!$validator->fails()) {
                                $data_chunk[] = $chunk;
                            }
                        }

                        if ($i == 50) {
                            $batch->add([new TransactionJob($data_chunk)]);
                            $data_chunk = [];
                            $i = 1;
                        } else {
                            $i++;
                        }
                        $j++;
                    }

                    if (!empty($data_chunk)) {
                        $batch->add([new TransactionJob($data_chunk)]);
                    }
                }

                return json_encode($batch);
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function batch(String $batchId)
    {
        return Bus::findBatch($batchId);
    }
}


class ChunkReadFilter implements IReadFilter
{
    private $startRow = 0;
    private $endRow   = 0;
    private $columns;

    public function __construct($columns)
    {
        $this->columns  = $columns;
    }

    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow   = $startRow + $chunkSize;
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        if ($row >= $this->startRow && $row < $this->endRow && in_array($columnAddress, $this->columns)) {
            return true;
        }
        return false;
    }
}
