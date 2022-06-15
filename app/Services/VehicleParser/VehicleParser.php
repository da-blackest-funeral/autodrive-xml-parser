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
         * @var Collection
         */
        private Collection $groups;

        /**
         * @param string $file
         * @return void
         */
        public function parse(string $file): void {
            $vehicles = collect(
                XmlToArray::convert(file_get_contents($file))['vehicle']
            )->take(30);

            $this->groups = $vehicles->pluck('equipment')
                ->whereNotNull()
                ->values()
                ->pluck('group')
                ->collapse()
                ->unique('@attributes.id');

            $this->deleteVehicles($vehicles->pluck('id'));
            $this->parseAllVehicles($vehicles);
        }

        /**
         * @param Collection $vehicles
         * @return void
         */
        private function parseAllVehicles(Collection $vehicles): void {
            $existingVehiclesIds = Vehicle::all('id')->pluck('id');

            foreach ($vehicles as $vehicle) {
                $this->attributes = [];
                $this->parseAttributes($vehicle);
                $formattedVehicle = $this->formatVehicle($vehicle);

                if (!$existingVehiclesIds->contains($vehicle['id'])) {
                    Vehicle::create(array_merge($formattedVehicle, $this->attributes));
                }

                if (!empty($vehicle['equipment'])) {
                    $this->syncRelation($vehicle);
                }
            }
        }

        /**
         * @param Collection $ids
         * @return void
         */
        private function deleteVehicles(Collection $ids) {
            Vehicle::query()
                ->whereNotIn('id', $ids)
                ->delete();
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

            $this->createGroups();

            foreach ($equipment['group'] as $groupData) {
                $elements[] = $this->parseElements(
                    $groupData['element'],
                    $groupData['@attributes']['id'],
                    $vehicleId
                );
            }

            return $elements ?? [];
        }

        /**
         * @return void
         */
        private function createGroups(): void {
            $existingGroups = Group::all('id')->pluck('id');

            foreach ($this->groups as $group) {
                if (!$existingGroups->contains($group['@attributes']['id'])) {
                    Group::create($group['@attributes']);
                }
            }
        }

        /**
         * @param array $elementsData
         * @param int $groupId
         * @param int $vehicleId
         * @return array
         */
        private function parseElements(array $elementsData, int $groupId, int $vehicleId): array {
            $existingElements = Element::all('name')->pluck('name');

            $result = [];
            foreach ($elementsData as $element) {
                if ($this->elementAlreadyExists($element, $existingElements)) {
                    continue;
                }

                if (isset($element['@content'])) {
                    $element = Element::create([
                        'name' => $element['@content'],
                    ]);
                } elseif (is_array($element)) {
                    $element = Element::create($element);
                } else {
                    $element = Element::create([
                        'name' => $element,
                    ]);
                }

                $result[] = [
                    'element_id' => $element->id,
                    'group_id' => $groupId,
                    'vehicle_id' => $vehicleId
                ];
            }

            return $result;
        }

        /**
         * @param array|string $element
         * @param Collection $existingElements
         * @return bool
         */
        private function elementAlreadyExists(
            array|string $element,
            Collection $existingElements
        ): bool {
            return !isset($element['@content']) ||
                $existingElements->contains($element['@content']);
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
