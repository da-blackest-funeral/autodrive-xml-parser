<?php

namespace App\Console\Commands;

use App\Services\VehicleParser\VehicleParser;
use Illuminate\Console\Command;

class ParseXmlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xml:parse {file=/home/vagrant/code/IDS/storage/app/xml/data.xml}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parsing an xml file of vehicle';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $parser = new VehicleParser();
        $parser->parse($this->argument('file'));

        echo "Successfully parsed the {$this->argument('file')}!" . PHP_EOL;
        return 0;
    }
}
