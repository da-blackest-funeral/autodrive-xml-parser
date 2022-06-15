<?php

    namespace App\Services\VehicleParser;

    class VehicleFormatter
    {
        /**
         * @param array $vehicle
         * @return array
         */
        public function keysToSnake(array $vehicle) {
            return $vehicle = collect($vehicle)->mapWithKeys(function ($attribute, $key) {
                return [
                    \Str::snake($key) => $attribute,
                ];
            })->map(function ($attribute) {
                if (is_array($attribute) && empty($attribute)) {
                    return '';
                }
                return $attribute;
            })->toArray();
        }

        /**
         * @param array $vehicle
         * @return array
         */
        public function removeUnusedKeys(array $vehicle) {
            return \Arr::except($vehicle, [
                'dealer',
                'brand',
                'model',
                'generation',
                'body_configuration',
                'modification',
                'category',
                'complectation',
                'equipment',
            ]);
        }
    }
