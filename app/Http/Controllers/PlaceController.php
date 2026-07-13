<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\HelperService;
use App\Services\ResponseService;
use Cerbero\JsonParser\JsonParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PlaceController extends Controller
{
    public function countryIndex()
    {
        ResponseService::noAnyPermissionThenRedirect(['country-list', 'country-create', 'country-update', 'country-delete']);
        $countries = JsonParser::parse(resource_path('countries.json'))->pointers(['/-/name', '/-/id', '/-/emoji'])->toArray();
        $dbCountries = Country::select('name')->get();
        foreach ($countries as $key => $country) {
            $countries[$key]['is_already_exists'] = $dbCountries->contains(static function ($dbCountry) use ($country) {
                return $country['name'] == $dbCountry->name;
            });
        }

        return view('places.country', compact('countries'));
    }

    public function countryShow(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('country-list');
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 15);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'DESC');

            $sql = Country::select(['id', 'name', 'emoji']);

            if (! empty($request->search)) {
                $sql = $sql->search($request->search);
            }
            $total = $sql->count();
            $sql = $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $result = $sql->get();
            $bulkData = [];
            $bulkData['total'] = $total;
            $rows = [];
            foreach ($result as $key => $row) {
                $tempRow = $row->toArray();
                if (auth()->user()->can('country-delete')) {
                    $tempRow['operate'] = BootstrapTableService::deleteButton(route('countries.destroy', $row->id));
                }

                $rows[] = $tempRow;
            }
            $bulkData['rows'] = $rows;

            return response()->json($bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'CustomFieldController -> show');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function destroyCountry($id)
    {
        try {
            Country::find($id)->delete();
            ResponseService::successResponse('Country deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'PlaceController -> destroyCountry');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function stateSearch(Request $request)
    {
        try {
            ResponseService::noAnyPermissionThenRedirect([
                'state-list', 'state-create', 'state-update',
                'city-list', 'city-create', 'city-update',
                'area-list', 'area-create', 'area-update',
                'slider-list', 'slider-create', 'slider-update',
            ]);
            $states = State::where('country_id', $request->country_id)->select(['id', 'name'])->orderBy('name', 'ASC')->get();
            ResponseService::successResponse('States Fetched Successfully', $states);
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th, 'PlaceController -> stateSearch');
            ResponseService::errorResponse();
        }
    }

    public function stateIndex()
    {
        ResponseService::noAnyPermissionThenRedirect(['state-list', 'state-create', 'state-update', 'state-delete']);
        $countries = Country::get();

        return view('places.state', compact('countries'));
    }

    public function stateShow(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('state-list');
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 15);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'DESC');

            $sql = State::with('country:id,name,emoji');

            if (! empty($request->filter)) {
                $sql = $sql->filter(json_decode($request->filter, false, 512, JSON_THROW_ON_ERROR));
            }

            if (! empty($request->search)) {
                $sql = $sql->search($request->search);
            }
            $total = $sql->count();
            $sql = $sql->sort($sort, $order)->skip($offset)->take($limit);
            $result = $sql->get();
            $bulkData = [];
            $bulkData['total'] = $total;
            $rows = [];
            foreach ($result as $key => $row) {
                $tempRow = $row->toArray();
                $tempRow['country_name'] = $row->country->name;
                $rows[] = $tempRow;
            }

            $bulkData['rows'] = $rows;

            return response()->json($bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'CustomFieldController -> show');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function citySearch(Request $request)
    {
        try {
            ResponseService::noAnyPermissionThenRedirect([
                'city-list', 'city-create', 'city-update',
                'area-list', 'area-create', 'area-update',
                'slider-list', 'slider-create', 'slider-update',
            ]);
            $cities = City::where('state_id', $request->state_id)->select(['id', 'name'])->orderBy('name', 'ASC')->get();
            ResponseService::successResponse('Cities fetched Successfully', $cities);
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th, 'PlaceController -> citySearch');
            ResponseService::errorResponse();
        }
    }

    public function cityIndex()
    {
        ResponseService::noAnyPermissionThenRedirect(['city-list', 'city-create', 'city-update', 'city-delete']);
        $countries = Country::get();

        $states = State::get();

        return view('places.city', compact('countries', 'states'));
    }

    public function addCity(Request $request)
    {
        ResponseService::noPermissionThenRedirect('city-create');

        $validator = Validator::make($request->all(), [
            'name.*' => 'required|string',
            'latitude.*' => 'nullable|numeric',
            'longitude.*' => 'nullable|numeric',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
        ], [], [
            'name.*' => 'City name',
            'latitude.*' => 'Latitude',
            'longitude.*' => 'Longitude',
            'country_id' => 'Country',
            'state_id' => 'State',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $state = State::findOrFail($request->state_id);
            $country = Country::findOrFail($request->country_id);

            $cityData = [];

            foreach ($request->name as $index => $name) {
                // Check if city already exists
                $exists = City::where('name', $name)
                    ->where('state_id', $request->state_id)
                    ->where('country_id', $request->country_id)
                    ->exists();

                if ($exists) {
                    ResponseService::validationError("City '{$name}' already exists in this state and country.");
                }
                $cityData[] = [
                    'name' => $name,
                    'state_id' => $request->state_id,
                    'country_id' => $request->country_id,
                    'state_code' => $state->state_code,
                    'country_code' => $country->iso2,
                    'latitude' => $request->latitude[$index] ?? null,
                    'longitude' => $request->longitude[$index] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            City::insert($cityData);
            ResponseService::successResponse('Cities added successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'message', 'The city already exists.');
            ResponseService::errorResponse();
        }
    }

    public function cityShow(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('city-list');
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 15);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'DESC');

            $sql = City::with('state:id,name', 'country:id,name,emoji');

            if (! empty($request->search)) {
                $sql = $sql->search($request->search);
            }
            if (! empty($request->filter)) {
                $sql = $sql->filter(json_decode($request->filter, false, 512, JSON_THROW_ON_ERROR));
            }
            $total = $sql->count();
            $sql = $sql->sort($sort, $order)->skip($offset)->take($limit);
            $result = $sql->get();
            $bulkData = [];
            $bulkData['total'] = $total;
            $rows = [];
            foreach ($result as $key => $row) {
                $tempRow = $row->toArray();
                $operate = '';
                if (Auth::user()->can('city-update')) {
                    $operate .= BootstrapTableService::editButton(route('city.update', $row->id), true, '#editModal', 'cityEvents', $row->id);
                }
                if (Auth::user()->can('city-delete')) {
                    $operate .= BootstrapTableService::deleteButton(route('city.destroy', $row->id));
                }
                $tempRow['state_name'] = $row->state->name;
                $tempRow['country_name'] = $row->country->name;
                $tempRow['state_id'] = $row->state->id;
                $tempRow['country_id'] = $row->country->id;
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }
            $bulkData['rows'] = $rows;

            return response()->json($bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'PlaceController -> show');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function updateCity(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('city-update');
        $validator = Validator::make($request->all(), [
            'name' => 'Required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $city = City::findOrFail($id);
            $data = $request->all();
            $city->update($data);
            ResponseService::successResponse('city updated successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Place Controller -> update');
            ResponseService::errorResponse();
        }
    }

    public function destroyCity(string $id)
    {
        try {
            ResponseService::noPermissionThenSendJson('city-delete');
            City::findOrFail($id)->delete();
            ResponseService::successResponse('city delete successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Place Controller -> destroy');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function importCountry(Request $request)
    {
        ResponseService::noPermissionThenSendJson('country-create');
        $validator = Validator::make($request->all(), [
            'countries' => 'required|array',
            'countries.*' => 'integer',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $country_id = $request->countries;
            DB::beginTransaction();
            foreach (JsonParser::parse(resource_path('world.json')) as $country) {
                if (in_array($country['id'], $country_id, false)) {
                    Country::create([
                        ...$country,
                        'timezones' => json_encode($country['timezones'], JSON_THROW_ON_ERROR),
                        'region_id' => null,
                        'subregion_id' => null,
                    ]);

                    foreach ($country['states'] as $state) {
                        State::create([
                            ...$state,
                            'country_id' => $country['id'],
                        ]);

                        $cities = [];
                        foreach ($state['cities'] as $city) {
                            $cities[] = [
                                ...$city,
                                'state_id' => $state['id'],
                                'state_code' => $state['state_code'],
                                'country_id' => $country['id'],
                                'country_code' => $country['iso2'],
                            ];
                        }

                        City::upsert($cities, ['name', 'state_id', 'country_id'], ['state_code', 'country_code', 'latitude', 'longitude', 'flag', 'wikiDataId']);
                    }

                    /* Stop the JSON file reading if country_id array is empty */
                    unset($country_id[array_search($country['id'], $country_id, true)]);
                    if (empty($country_id)) {
                        break;
                    }
                }
            }
            DB::commit();
            ResponseService::successResponse('Country imported successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'CustomFieldController -> show');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

     public function createArea()
    {
        ResponseService::noAnyPermissionThenRedirect(['area-list', 'area-create', 'area-update', 'area-delete']);
        $countries = Country::get();
        $states = State::get();
        $cities = city::get();

        return view('places.area', compact('countries', 'states', 'cities'));
    }

    public function addArea(Request $request)
    {
        ResponseService::noPermissionThenRedirect('area-create');

        // Drop area rows the user added but left completely blank, so an unused
        // extra row doesn't block the whole submission.
        $names      = (array) $request->input('name', []);
        $latitudes  = (array) $request->input('latitude', []);
        $longitudes = (array) $request->input('longitude', []);

        $keep = array_values(array_filter(array_keys($names), static function ($i) use ($names, $latitudes, $longitudes) {
            return trim((string) ($names[$i] ?? '')) !== ''
                || trim((string) ($latitudes[$i] ?? '')) !== ''
                || trim((string) ($longitudes[$i] ?? '')) !== '';
        }));

        $request->merge([
            'name'      => array_values(array_map(static fn($i) => $names[$i] ?? null, $keep)),
            'latitude'  => array_values(array_map(static fn($i) => $latitudes[$i] ?? null, $keep)),
            'longitude' => array_values(array_map(static fn($i) => $longitudes[$i] ?? null, $keep)),
        ]);

        $validator = Validator::make($request->all(), [
            // Guard the array itself, otherwise a missing `name` slips past `name.*`
            // and blows up in the foreach below.
            'name' => 'required|array|min:1',
            // Must contain at least one letter — blocks pure-number/symbol garbage.
            'name.*' => ['required', 'string', 'min:2', 'max:100', 'regex:/^(?=.*\p{L})[\p{L}\p{N}\s\-&\'.,()\/]+$/u'],
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            // latitude is decimal(10,8) and longitude decimal(11,8) — keep values in
            // real-world range so MySQL never silently clamps them.
            'latitude.*' => 'nullable|numeric|between:-90,90',
            'longitude.*' => 'nullable|numeric|between:-180,180',
        ], [
            'name.required' => 'Please add at least one area.',
            'name.*.required' => 'Area name is required — remove any empty area rows.',
            'name.*.regex' => 'Area name must contain letters — it cannot be only numbers or symbols.',
            'name.*.min' => 'Area name must be at least 2 characters.',
            'country_id.required' => 'Please select a Country.',
            'state_id.required' => 'Please select a State.',
            'city_id.required' => 'Please select a City.',
            'latitude.*.between' => 'Latitude must be between -90 and 90.',
            'longitude.*.between' => 'Longitude must be between -180 and 180.',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $state = State::findOrFail($request->state_id);
            $area = [];
            foreach ($request->name as $index => $name) {
                $area[] = [
                    'name' => $name,
                    'city_id' => $request->city_id,
                    'state_id' => $request->state_id,
                    'country_id' => $request->country_id,
                    'state_code' => $state->state_code,
                    'latitude' => $request->latitude[$index] ?? null,
                    'longitude' => $request->longitude[$index] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Area::insert($area);
            ResponseService::successResponse('Area Added Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'place Controller -> store');
            ResponseService::errorResponse();
        }
    }

    public function areaShow(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('area-list');
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'ASC');

            $sql = Area::with('city:id,name', 'state:id,name', 'country:id,name')->orderBy($sort, $order);

            if (! empty($_GET['search'])) {
                $search = $_GET['search'];
                $sql->where('id', 'LIKE', "%$search%")
                    ->orwhere('name', 'LIKE', "%$search%")
                    ->orwhere('latitude', 'LIKE', "%$search%")
                    ->orwhere('longitude', 'LIKE', "%$search%");
            }
            if (! empty($request->filter)) {
                $sql = $sql->filter(json_decode($request->filter, false, 512, JSON_THROW_ON_ERROR));
            }

            $total = $sql->count();
            $sql->skip($offset)->take($limit);
            $result = $sql->get();
            $bulkData = [];
            $bulkData['total'] = $total;
            $rows = [];
            foreach ($result as $key => $row) {
                $tempRow = $row->toArray();
                $operate = '';
                if (Auth::user()->can('area-update')) {
                    $operate .= BootstrapTableService::editButton(route('area.update', $row->id), true, '#editModal', 'areaEvents', $row->id);
                }
                if (Auth::user()->can('area-delete')) {
                    $operate .= BootstrapTableService::deleteButton(route('area.destroy', $row->id));
                }
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }

            $bulkData['rows'] = $rows;

            return response()->json($bulkData);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'PlaceController --> show');
            ResponseService::errorResponse();
        }
    }

    public function edit(string $id) {}

    public function updateArea(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('area-update');
        $validator = Validator::make($request->all(), [
            'name' => 'Required|string',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $area = Area::findOrFail($id);
            $data = $request->all();
            $area->update($data);
            ResponseService::successResponse('Area updated successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Area Controller -> update');
            ResponseService::errorResponse();
        }
    }

    public function destroyArea(string $id)
    {
        try {
            ResponseService::noPermissionThenSendJson('area-delete');
            Area::findOrFail($id)->delete();
            ResponseService::successResponse('Area delete successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Place Controller -> destroy');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function showCountryTranslations(Request $request)
    {
        ResponseService::noPermissionThenRedirect('country-update');
        $countries = Country::get();
        $languages = CachingService::getLanguages()->where('code', '!=', 'en')->values();

        return view('places.country_translation', compact('countries', 'languages'));
    }

    public function updateCountriesTranslations(Request $request)
    {
        ResponseService::noPermissionThenSendJson('country-update');

        $validator = Validator::make($request->all(), [
            'translations' => 'required|array',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            foreach ($request->translations as $languageId => $translations) {
                foreach ($translations as $countryId => $translatedName) {
                    if (! empty($translatedName)) {
                        HelperService::storeTranslations([
                            ['translatable_id' => $countryId, 'translatable_type' => \App\Models\Country::class, 'key' => 'name', 'value' => $translatedName, 'language_id' => $languageId],
                        ]);
                    }
                }
            }

            ResponseService::successResponse('Country translations updated successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Country Controller -> updateTranslations');
            ResponseService::errorResponse();
        }
    }

    public function showStatesTranslations(Request $request)
    {
        ResponseService::noPermissionThenRedirect('state-update');
        $States = State::with('translations')->get();
        $countries = Country::get();
        $languages = CachingService::getLanguages()->where('code', '!=', 'en')->values();

        return view('places.state_translation', compact('countries', 'States', 'languages'));
    }

    public function updateStatesTranslations(Request $request)
    {
        ResponseService::noPermissionThenSendJson('state-update');

        $validator = Validator::make($request->all(), [
            'translations' => 'required|array',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            foreach ($request->translations as $languageId => $translations) {
                foreach ($translations as $countryId => $translatedName) {
                    if (! empty($translatedName)) {
                        HelperService::storeTranslations([
                            ['translatable_id' => $countryId, 'translatable_type' => \App\Models\State::class, 'key' => 'name', 'value' => $translatedName, 'language_id' => $languageId],
                        ]);
                    }
                }
            }

            ResponseService::successResponse('State translations updated successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Country Controller -> updateTranslations');
            ResponseService::errorResponse();
        }
    }

    public function showCitiesTranslations()
    {
        ResponseService::noPermissionThenRedirect('city-update');
        // $cities = City::with('translations')->get();
        $countries = Country::with('states')->get();
        $languages = CachingService::getLanguages()->where('code', '!=', 'en')->values();

        return view('places.city_translation', compact('countries', 'languages'));
    }

    public function loadStateCities(Request $request, $stateId)
    {
        $perPage = $request->input('per_page', 50);
        $state = State::findOrFail($stateId);
        $cities = City::where('state_id', $stateId)
            ->with('translations')
            ->get();

        $languages = CachingService::getLanguages()->where('code', '!=', 'en')->values();

        return view('places.city_translation_tab', compact('state', 'cities', 'languages'));
    }

    public function updateCitiesTranslations(Request $request)
    {
        ResponseService::noPermissionThenSendJson('city-update');

        $validator = Validator::make($request->all(), [
            'translations' => 'required|array',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            foreach ($request->translations as $languageId => $translations) {
                foreach ($translations as $cityId => $translatedName) {
                    if (! empty($translatedName)) {
                        HelperService::storeTranslations([
                            ['translatable_id' => $cityId, 'translatable_type' => \App\Models\City::class, 'key' => 'name', 'value' => $translatedName, 'language_id' => $languageId],
                        ]);
                    }
                }
            }

            ResponseService::successResponse('City translations updated successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'City Translation -> update');
            ResponseService::errorResponse();
        }
    }

    public function areaTranslation()
    {
        $countries = Country::get();

        return view('places.area_translation', compact('countries'));
    }

    public function loadCityAreas(Request $request, $cityId)
    {
        $city = City::findOrFail($cityId);
        $areas = Area::where('city_id', $cityId)
            ->with('translations')
            ->get();

        $languages = CachingService::getLanguages()->where('code', '!=', 'en')->values();

        return view('places.area_translation_tab', compact('city', 'areas', 'languages'));
    }

    public function updateAreasTranslations(Request $request)
    {
        ResponseService::noPermissionThenSendJson('area-update');

        $validator = Validator::make($request->all(), [
            'translations' => 'required|array',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            foreach ($request->translations as $languageId => $translations) {
                foreach ($translations as $areaId => $translatedName) {
                    if (! empty($translatedName)) {
                        HelperService::storeTranslations([
                            ['translatable_id' => $areaId, 'translatable_type' => \App\Models\Area::class, 'key' => 'name', 'value' => $translatedName, 'language_id' => $languageId],
                        ]);
                    }
                }
            }

            ResponseService::successResponse('Area translations updated successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Area Translation -> update');
            ResponseService::errorResponse();
        }
    }
}
