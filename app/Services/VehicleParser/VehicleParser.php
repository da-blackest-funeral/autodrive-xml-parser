<?php

    namespace App\Services\VehicleParser;

    use App\Models\BodyConfiguration;
    use App\Models\Brand;
    use App\Models\CarModel;
    use App\Models\Category;
    use App\Models\Complectation;
    use App\Models\Dealer;
    use App\Models\Element;
    use App\Models\Generation;
    use App\Models\Group;
    use App\Models\Modification;
    use App\Models\Vehicle;
    use Illuminate\Support\Collection;
    use Mtownsend\XmlToArray\XmlToArray;

    class VehicleParser implements Parser
    {
        /**
         * @var array
         */
        private array $attributes = [];

        /**
         * @param string|null $file
         * @return void
         */
        public function parse(string $file = null): void {
            if (is_null($file)) {
                $file = storage_path('app/xml/data.xml');
            }

            $vehicles = collect(
                XmlToArray::convert(file_get_contents($file))['vehicle']
            )->take(30);

            $this->parseAllVehicles($vehicles);
        }

        /**
         * @param Collection $vehicles
         * @return void
         */
        private function parseAllVehicles(Collection $vehicles): void {
            foreach ($vehicles as $vehicleData) {
                $this->attributes = [];
                $this->parseAttributes($vehicleData);
                $formattedVehicle = $this->formatVehicle($vehicleData);
                /** @var Vehicle $vehicle */
                Vehicle::firstOrCreate(
                    array_merge($formattedVehicle, $this->attributes)
                );

                if (!empty($vehicleData['equipment'])) {
                    $this->syncRelation($vehicleData);
                }
            }
        }

        /**
         * @param array $vehicleData
         * @return void
         */
        private function syncRelation(array $vehicleData): void {
            $groups = $this->parseGroups($vehicleData['equipment'], $vehicleData['id']);
            foreach ($groups as $group) {
                foreach ($group as $attributes) {
                    \DB::table('element_group_vehicle')
                        ->insert($attributes);
                }
            }
        }

        /**
         * @param array $vehicle
         * @return array
         */
        private function formatVehicle(array $vehicle): array {
            $formatter = new VehicleFormatter();
            $vehicle = $formatter->keysToSnake($vehicle);
            return $formatter->removeUnusedKeys($vehicle);
        }

        /**
         * @param array $vehicle
         * @return void
         */
        private function parseAttributes(array $vehicle): void {
            if (!empty($vehicle['dealer'])) {
                $this->createDealer($vehicle['dealer']['@content']);
            }

            $this->createCategory($vehicle['category']['@content']);
            $this->createBrand($vehicle['brand']['@content']);
            $this->createModel($vehicle['model']['@content']);
            $this->createGeneration($vehicle['generation']['@content']);
            $this->createBodyConfiguration($vehicle['bodyConfiguration']['@content']);
            $this->createModification($vehicle['modification']['@content']);

            if (!empty($vehicle['complectation'])) {
                $this->createComplectation($vehicle['complectation']['@content']);
            } else {
                $this->attributes['complectation_id'] = 1;
            }
        }

        /**
         * @param array $equipment
         * @param int $vehicleId
         * @return array
         */
        private function parseGroups(array $equipment, int $vehicleId): array {
            if (empty($equipment)) {
                return [];
            }

            foreach ($equipment['group'] as $groupData) {
                /** @var Group $group */
                $group = Group::firstOrCreate([
                    'name' => $groupData['@attributes']['name'],
                ]);

                $elements[] = $this->parseElements(
                    $groupData['element'],
                    $groupData['@attributes']['id'],
                    $vehicleId
                );
            }

            return $elements ?? [];
        }

        /**
         * @param array $elementsData
         * @param int $groupId
         * @param int $vehicleId
         * @return array
         */
        private function parseElements(array $elementsData, int $groupId, int $vehicleId): array {
            $elements = [];
            foreach ($elementsData as $element) {
                if (isset($element['@content'])) {
                    $element = Element::firstOrCreate([
                        'name' => $element['@content'],
                    ]);
                } elseif (is_array($element)) {
                    $element = Element::firstOrCreate($element);
                } else {
                    $element = Element::firstOrCreate([
                        'name' => $element,
                    ]);
                }

                $elements[] = [
                    'element_id' => $element->id,
                    'group_id' => $groupId,
                    'vehicle_id' => $vehicleId
                ];
            }

            return $elements;
        }

        /**
         * @param string $name
         * @return void
         */
        private function createDealer(string $name): void {
            $this->attributes['dealer_id'] = Dealer::firstOrCreate([
                'name' => $name,
            ])->id;
        }

        /**
         * @param string $name
         * @return void
         */
        private function createCategory(string $name): void {
            $this->attributes['category_id'] = Category::firstOrCreate([
                'name' => $name,
            ])->id;
        }

        /**
         * @param string $name
         * @return void
         */
        private function createBrand(string $name): void {
            $this->attributes['brand_id'] = Brand::firstOrCreate([
                'name' => $name,
            ])->id;
        }

        /**
         * @param string $name
         * @return void
         */
        private function createModel(string $name): void {
            $this->attributes['model_id'] = CarModel::firstOrCreate([
                'name' => $name,
            ])->id;
        }

        /**
         * @param string $name
         * @return void
         */
        private function createGeneration(string $name): void {
            $this->attributes['generation_id'] = Generation::firstOrCreate([
                'name' => $name,
            ])->id;
        }

        /**
         * @param string $name
         * @return void
         */
        private function createBodyConfiguration(string $name): void {
            $this->attributes['body_configuration_id'] = BodyConfiguration::firstOrCreate([
                'name' => $name,
            ])->id;
        }

        /**
         * @param string $name
         * @return void
         */
        private function createModification(string $name): void {
            $this->attributes['modification_id'] = Modification::firstOrCreate([
                'name' => $name,
            ])->id;
        }

        /**
         * @param string $name
         * @return void
         */
        private function createComplectation(string $name): void {
            $this->attributes['complectation_id'] = Complectation::firstOrCreate([
                'name' => $name,
            ])->id;
        }
    }
