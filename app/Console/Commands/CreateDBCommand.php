<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateDBCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:database {dbname?} {charset?} {collation?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try{
            $dbname = $this->argument('dbname') ?: config("database.connections.mysql.database");
            $charset = $this->argument('charset') ?: config("database.connections.mysql.charset",'utf8mb4');
            $collation = $this->argument('collation') ?: config("database.connections.mysql.collation",'utf8mb4_unicode_ci');
    
            config(["database.connections.mysql.database" => null]);
    
            $query = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET $charset COLLATE $collation;";
            DB::statement($query);
    
            config(["database.connections.mysql.database" => $dbname]);
        }
        catch (\Exception $e){
            $this->error($e->getMessage());
        }
        return 0;
    }
}
