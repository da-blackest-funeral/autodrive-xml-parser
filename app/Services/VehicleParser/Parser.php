<?php

    namespace App\Services\VehicleParser;

    interface Parser
    {
        public function parse(string $file): void;
    }
