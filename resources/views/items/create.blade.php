@extends('layouts.main')

@section('title')
    {{ __('Create Advertisement') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('advertisement.store') }}" class="create-form" data-parsley-validate
                    data-pre-submit-function="validateAdvertisementForm" data-success-function="handleAdvertisementSuccess" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="category_id" id="category_id" value="">

                    <ul class="nav nav-tabs" id="addItemTabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#categories"
                                data-tab-index="0">{{ __('Categories') }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#listing"
                                data-tab-index="2">{{ __('Listing Details') }}</a></li>
                        <li class="nav-item" id="custom-tab-item" style="display: none;"><a class="nav-link"
                                data-bs-toggle="tab" href="#custom" data-tab-index="3">{{ __('Other Details') }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#images"
                                data-tab-index="4">{{ __('Product Images') }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#address"
                                data-tab-index="5">{{ __('Address') }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#seo"
                                data-tab-index="6">{{ __('SEO') }}</a></li>
                    </ul>

                    <div class="tab-content pt-3">

                        {{-- Listing Details --}}
                        <div class="tab-pane fade" id="listing">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <label class="form-label mb-1">{{ __('Selected category') }}</label>
                                            <div id="selected-category-breadcrumb" class="text-primary"></div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <label class="me-2 mb-0">{{ __('Select Language') }}:</label>
                                            <select class="form-control form-control-sm" id="details-language-selector" style="width: 200px;">
                                                @foreach ($languages as $lang)
                                                    <option value="{{ $lang->id }}" data-code="{{ $lang->code }}" {{ $lang->id == $defaultLanguage->id ? 'selected' : '' }}>
                                                        {{ $lang->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Translatable Fields - Title and Description Only --}}
                            <div class="row">
                                {{-- Default Language Fields - Always Visible --}}
                                <div class="col-12 language-fields default-language-fields" data-language-id="{{ $defaultLanguage->id }}">
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label>{{ __('Title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name-input" value="{{ old('name') }}"
                                                class="form-control" required placeholder="{{ __('Enter title') }}">
                                </div>

                                        <div class="col-12 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label>{{ __('Description') }} <span class="text-danger">*</span></label>
                                                @if($geminiEnabled ?? false)
                                                    <button type="button" class="btn btn-sm btn-outline-primary generate-description-btn">
                                                        <i class="fas fa-magic"></i> {{ __('Generate with AI') }}
                                                        <span class="spinner-border spinner-border-sm d-none description-loading"></span>
                                                    </button>
                                                @endif
                                            </div>
                                            <textarea name="description" id="description-input" class="form-control" rows="5" required placeholder="{{ __('Enter description') }}">{{ old('description') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                {{-- Other Language Fields - Only Name and Description --}}
                                @foreach ($languages as $lang)
                                    @if ($lang->id != $defaultLanguage->id)
                                        <div class="col-12 language-fields other-language-fields" data-language-id="{{ $lang->id }}" style="display: none;">
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <label>{{ __('Title') }}</label>
                                                    <input type="text" name="translations[{{ $lang->id }}][name]" 
                                                        class="form-control translation-name" 
                                                        data-lang-id="{{ $lang->id }}"
                                                        placeholder="{{ __('Enter title') }}">
                                                </div>

                                                <div class="col-12 mb-3">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <label>{{ __('Description') }}</label>
                                                        @if($geminiEnabled ?? false)
                                                            <button type="button" class="btn btn-sm btn-outline-primary generate-description-btn">
                                                                <i class="fas fa-magic"></i> {{ __('Generate with AI') }}
                                                                <span class="spinner-border spinner-border-sm d-none description-loading"></span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <textarea name="translations[{{ $lang->id }}][description]" 
                                                        class="form-control translation-description" 
                                                        data-lang-id="{{ $lang->id }}" rows="5"
                                                        placeholder="{{ __('Enter description') }}"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            {{-- Non-Translatable Fields - Always Visible --}}
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label>{{ __('Currency') }}</label>
                                    <select class="form-control select2" id="currency" name="currency_id" required>
                                        <option value="">{{ __('--Select Currency--') }}</option>
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->id }}" data-iso-code="{{ $currency->iso_code ?? '' }}" data-symbol="{{ $currency->symbol ?? '' }}">{{ $currency->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 mb-3" id="price-field">
                                    <label>{{ __('Price') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="price" id="price-input" value="{{ old('price') }}"
                                        class="form-control" required placeholder="{{ __('Enter price') }}">
                                </div>

                                <div class="col-12 mb-3" id="salary-fields" style="display: none;">
                                    <div class="row">
                                        <div class="col-12 mb-2">
                                    <label>{{ __('Min Salary') }}</label>
                                            <input type="number" name="min_salary" id="min-salary-input" class="form-control"
                                                value="{{ old('min_salary') }}" placeholder="{{ __('Enter minimum salary') }}">
                                        </div>
                                        <div class="col-12">
                                    <label>{{ __('Max Salary') }}</label>
                                    <input type="number" name="max_salary" id="max-salary-input" class="form-control"
                                                value="{{ old('max_salary') }}" placeholder="{{ __('Enter maximum salary') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label>{{ __('Phone Number') }}</label>
                                    <input type="tel" name="contact" id="contact-input"
                                        value="{{ old('contact', auth()->user()->phone ?? '') }}" class="form-control"
                                        placeholder="{{ __('Enter phone number') }}">
                                    <input type="hidden" name="country_code" id="country-code-input" value="{{ old('country_code', auth()->user()->country_code ?? '') }}">
                                    <input type="hidden" name="region_code" id="region-code-input" value="{{ old('region_code', auth()->user()->region_code ?? '') }}">
                                </div>

                                <div class="col-12 mb-3">
                                    <label>{{ __('Video Link') }}</label>
                                    <input type="text" name="video_link" id="video_link" class="form-control"
                                        value="{{ old('video_link') }}"
                                        placeholder="{{ __('Enter video URL (e.g., https://www.youtube.com/watch?v=...)') }}">
                                </div>

                                <div class="col-12 mb-3">
                                    <label>{{ __('Slug') }}</label>
                                    <input type="text" name="slug" value="{{ old('slug') }}" class="form-control"
                                        placeholder="{{ __('Enter slug (optional)') }}">
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-primary btn-prev-tab"
                                    data-prev-tab="categories">{{ __('Previous') }}</button>
                                <button type="button" class="btn btn-primary btn-next-tab"
                                    data-next-tab="custom-or-images">{{ __('Next') }}</button>
                            </div>
                        </div>

                        {{-- Categories Tab --}}
                        <div class="tab-pane fade show active" id="categories">
                            <div class="row">
                                <div class="col-12">
                                    <div id="categories-container">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">{{ __('All Category') }}</h5>
                                            <button type="button" class="btn btn-secondary btn-sm" id="go-back-category-btn" style="display: none;">
                                                <i class="fas fa-arrow-left me-1"></i>{{ __('Go Back') }}
                                            </button>
                                        </div>
                                        <div id="breadcrumb-container" style="display: none; margin-bottom: 15px;">
                                            <nav aria-label="breadcrumb">
                                                <ol class="breadcrumb mb-0" id="category-breadcrumb"></ol>
                                            </nav>
                                        </div>
                                        <div id="categories-list" class="row"></div>
                                        <div id="load-more-container" class="text-center mt-3" style="display: none;">
                                            <button type="button" class="btn btn-primary" id="load-more-categories">
                                                {{ __('Load More') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        {{-- Other Details - Custom Fields --}}
                        <div class="tab-pane fade" id="custom">
                            <div class="text-muted">{{ __('Select a category to load custom fields.') }}</div>
                        </div>

                        {{-- Product Images --}}
                        <div class="tab-pane fade" id="images">
                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <label class="form-label">
                                        {{ __('Product Images') }}
                                        <i class="fas fa-info-circle" data-bs-toggle="tooltip" title="{{ __('Upload images for your advertisement. The first image will be highlighted and considered the main image.') }}"></i>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="upload-area" id="gallery-images-upload" 
                                         style="border: 2px dashed #ddd; border-radius: 8px; padding: 40px; text-align: center; background: #f9f9f9; cursor: pointer; min-height: 200px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                        <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #20c997; margin-bottom: 15px;"></i>
                                        <p class="mb-2" style="color: #666;">{{ __('Drag & Drop your files') }}</p>
                                        <p class="mb-3" style="color: #999;">{{ __('or') }}</p>
                                        <button type="button" class="btn btn-primary" id="gallery-images-btn" style="background: #20c997; border: none;">
                                            <i class="fas fa-upload me-2"></i>{{ __('Upload') }}
                                        </button>
                                        <input type="file" name="gallery_images[]" id="gallery-images-input" required class="d-none" multiple accept="image/png,image/jpeg,image/jpg">
                                        <div id="gallery-images-preview" class="mt-3 row g-2 w-100"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-primary btn-prev-tab"
                                    data-prev-tab="custom-or-images">{{ __('Previous') }}</button>
                                <button type="button" class="btn btn-primary btn-next-tab"
                                    data-next-tab="address">{{ __('Next') }}</button>
                            </div>
                        </div>

                        {{-- Address --}}
                        <div class="tab-pane fade" id="address">
                            <div class="row">
                                <div class="col-12">
                                    <label class="form-label mb-2">{{ __('Map Address') }}</label>
                                    
                                    <!-- Search and Locate Bar -->
                                    <div class="d-flex gap-2 mb-3" style="position: relative; z-index: 1000;">
                                        <div class="flex-grow-1 position-relative" style="z-index: 1001;">
                                            <i class="fas fa-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%); color: #999; z-index: 10;"></i>
                                            <input type="text" id="location-search" class="form-control ps-5" 
                                                   placeholder="{{ __('Select Location') }}" 
                                                   style="border-radius: 5px; z-index: 1;">
                                            <div id="search-results" class="position-absolute w-100 bg-white border rounded mt-1"
                                                 style="display: none; max-height: 200px; overflow-y: auto; z-index: 1050; box-shadow: 0 4px 12px rgba(0,0,0,0.15); top: 100%;"></div>
                                        </div>
                                        <button type="button" class="btn btn-primary" id="locate-me-btn" 
                                                style="background: #20c997; border: none; white-space: nowrap; z-index: 1;">
                                            <i class="fas fa-crosshairs me-2"></i>{{ __('Locate me') }}
                                        </button>
                                    </div>

                                    <div id="map"
                                        style="height: 500px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; z-index: 1; position: relative;"></div>

                                    <!-- Selected Address Display -->
                                    <div id="selected-address-display" class="card mb-3" style="display: none;">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-map-marker-alt text-primary me-3 mt-1" style="font-size: 24px;"></i>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">{{ __('Address') }}</h6>
                                                    <p class="mb-0 text-muted" id="selected-address-text"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if(($mapProvider ?? 'free_api') === 'free_api')
                                    <div class="text-center mb-3">
                                        <p class="text-muted mb-2">{{ __('Or') }}</p>
                                        <p class="mb-2">{{ __('What Is the Location Of the Advertisement You Are Selling') }}</p>
                                        <button type="button" class="btn btn-primary" id="add-location-btn" data-bs-toggle="modal" data-bs-target="#manualLocationModal">{{ __('Add Location') }}</button>
                                    </div>
                                    @endif

                                    <!-- Hidden inputs for form submission -->
                                    <input type="hidden" id="latitude-input" name="latitude" required />
                                    <input type="hidden" id="longitude-input" name="longitude" required />
                                    <input type="hidden" name="country_input" id="country-input">
                                    <input type="hidden" name="state_input" id="state-input">
                                    <input type="hidden" name="city_input" id="city-input">
                                    <input type="hidden" name="address" id="address-hidden">
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-primary btn-prev-tab"
                                    data-prev-tab="images">{{ __('Previous') }}</button>
                                <button type="button" class="btn btn-primary btn-next-tab"
                                    data-next-tab="seo">{{ __('Next') }}</button>
                            </div>
                        </div>

                        {{-- SEO Details --}}
                        <div class="tab-pane fade" id="seo">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5>{{ __('SEO Details') }}</h5>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($geminiEnabled ?? false)
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="generate-meta-btn">
                                                    <i class="fas fa-magic"></i> {{ __('Generate SEO with AI') }}
                                                    <span class="spinner-border spinner-border-sm d-none" id="meta-loading"></span>
                                                </button>
                                            @endif
                                            <label class="me-2 mb-0">{{ __('Select Language') }}:</label>
                                            <select class="form-control form-control-sm" id="seo-language-selector" style="width: 200px;">
                                                @foreach ($languages as $lang)
                                                    <option value="{{ $lang->id }}" {{ $lang->id == $defaultLanguage->id ? 'selected' : '' }}>
                                                        {{ $lang->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Default Language SEO Fields --}}
                            <div class="seo-language-fields" data-seo-language-id="{{ $defaultLanguage->id }}">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label>{{ __('Meta Title') }}</label>
                                        <input type="text" name="meta_title[{{ $defaultLanguage->id }}]" class="form-control" placeholder="{{ __('Enter meta title') }}">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label>{{ __('Meta Description') }}</label>
                                        <textarea name="meta_description[{{ $defaultLanguage->id }}]" class="form-control" rows="3" placeholder="{{ __('Enter meta description') }}"></textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label>{{ __('Meta Keywords') }}</label>
                                        <textarea name="meta_keywords[{{ $defaultLanguage->id }}]" class="form-control" rows="2" placeholder="{{ __('Enter meta keywords') }}"></textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label>{{ __('Schema') }}</label>
                                        <textarea name="schema[{{ $defaultLanguage->id }}]" class="form-control" rows="4" placeholder='{"@context": "https://schema.org", ...}'></textarea>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-info-circle"></i>
                                            {{ __('Schema is not auto-generated by AI. Please add it manually after saving the item, when image URLs and other required data are available.') }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            {{-- Other Language SEO Fields --}}
                            @foreach ($languages as $lang)
                                @if ($lang->id != $defaultLanguage->id)
                                    <div class="seo-language-fields" data-seo-language-id="{{ $lang->id }}" style="display: none;">
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label>{{ __('Meta Title') }} ({{ $lang->name }})</label>
                                                <input type="text" name="meta_title[{{ $lang->id }}]" class="form-control" placeholder="{{ __('Enter meta title') }}">
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label>{{ __('Meta Description') }} ({{ $lang->name }})</label>
                                                <textarea name="meta_description[{{ $lang->id }}]" class="form-control" rows="3" placeholder="{{ __('Enter meta description') }}"></textarea>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label>{{ __('Meta Keywords') }} ({{ $lang->name }})</label>
                                                <textarea name="meta_keywords[{{ $lang->id }}]" class="form-control" rows="2" placeholder="{{ __('Enter meta keywords') }}"></textarea>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label>{{ __('Schema') }} ({{ $lang->name }})</label>
                                                <textarea name="schema[{{ $lang->id }}]" class="form-control" rows="4" placeholder='{"@context": "https://schema.org", ...}'></textarea>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fas fa-info-circle"></i>
                                                    {{ __('Schema is not auto-generated by AI. Please add it manually after saving the item, when image URLs and other required data are available.') }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-primary btn-prev-tab"
                                    data-prev-tab="address">{{ __('Previous') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('Post') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    @if(($mapProvider ?? 'free_api') === 'free_api')
    <div class="modal fade" id="manualLocationModal" tabindex="-1" aria-labelledby="manualLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manualLocationModalLabel">{{ __('Manually Add Location') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <select class="form-select" id="manual-country-select">
                            <option value="">{{ __('Country') }}</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" data-name="{{ $country->name }}" data-lat="{{ $country->latitude }}" data-lng="{{ $country->longitude }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <select class="form-select" id="manual-state-select" disabled>
                            <option value="">{{ __('State') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <select class="form-select" id="manual-city-select" disabled>
                            <option value="">{{ __('City') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control" id="manual-address-input" rows="3" placeholder="{{ __('Enter address') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="manual-location-save">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('script')
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/leaflet.css') }}">
    <!-- Leaflet JS -->
    <script src="{{ asset('assets/js/leaflet.js') }}"></script>
    
    <!-- intl-tel-input CSS -->
    <link rel="stylesheet" href="{{ asset('assets/extensions/intl-tel-input/css/intlTelInput.css') }}">
    <!-- intl-tel-input JS -->
    <script src="{{ asset('assets/extensions/intl-tel-input/js/intlTelInput.min.js') }}"></script>
    
    <style>
        #addItemTabs .nav-link.tab-disabled {
            opacity: 0.45;
            color: #888 !important;
            background-color: #f5f5f5;
            pointer-events: none;
            cursor: not-allowed;
        }
        #contact-input {
            width: 100% !important;
        }
        .iti {
            width: 100%;
        }
        .iti__flag-container {
            z-index: 2;
        }
        .iti__selected-flag {
            z-index: 3;
        }
    </style>
    
    <script>
        $(document).ready(function() {
            // Languages array for JavaScript
            const languages = @json($languages);
            const defaultLanguageId = {{ $defaultLanguage->id }};
            
            let selectedCategoryId = null;
            let selectedCategoryName = '';
            let selectedCategoryPath = []; // Track full category path
            let currentParentId = null;
            let currentPage = 1;
            let hasMoreCategories = true;
            let categoryHistory = []; // Track navigation history

            // Language switching for Details tab - only show/hide translatable fields (name and description)
            $('#details-language-selector').on('change', function() {
                const selectedLangId = $(this).val();
                const defaultLangId = {{ $defaultLanguage->id }};
                
                // Hide all other language fields (non-default)
                $('#listing .other-language-fields').hide();
                
                // If default language is selected, show default fields
                if (selectedLangId == defaultLangId) {
                    $('#listing .default-language-fields').show();
                } else {
                    // Hide default language fields (only the translatable ones)
                    $('#listing .default-language-fields').hide();
                    // Show selected language fields (only name and description)
                    $(`#listing .other-language-fields[data-language-id="${selectedLangId}"]`).show();
                }
                
                // Ensure non-translatable fields are always visible
                // They are in a separate div outside language-fields containers, so they should remain visible
            });

            // Initialize: Show default language fields (name and description only)
            const defaultLangId = {{ $defaultLanguage->id }};
            $('#listing .default-language-fields').show();
            $('#listing .other-language-fields').hide();

            // Language switching for SEO tab
            $('#seo-language-selector').on('change', function() {
                const selectedLangId = $(this).val();
                $('.seo-language-fields').hide();
                $(`.seo-language-fields[data-seo-language-id="${selectedLangId}"]`).show();
            });

            // Initialize country code selector for phone number
            let phoneInputInitialized = false;
            let phoneIti = null;

            function initPhoneInput() {
                const phoneInput = document.getElementById('contact-input');
                if (!phoneInput || phoneInputInitialized) {
                    return;
                }

                // Check if intlTelInput is already initialized on this input
                if (phoneInput.classList.contains('iti-mobile') || phoneInput.closest('.iti')) {
                    phoneInputInitialized = true;
                    return;
                }

                // Wait for intlTelInput library to load
                if (typeof window.intlTelInput === 'undefined') {
                    setTimeout(initPhoneInput, 100);
                    return;
                }

                @php
                    $userInitialCountry = !empty(auth()->user()->region_code)
                        ? strtolower(auth()->user()->region_code)
                        : 'in';
                @endphp

                try {
                    const iti = window.intlTelInput(phoneInput, {
                        initialCountry: "{{ $userInitialCountry }}",
                        preferredCountries: ['us', 'gb', 'in', 'ca', 'au'],
                        separateDialCode: true,
                        utilsScript: "{{ asset('assets/extensions/intl-tel-input/js/utils.js') }}"
                    });

                    phoneIti = iti;
                    phoneInputInitialized = true;

                    // Strip dial code from a raw value string based on selected country
                    function stripDialCode(value) {
                        const countryData = iti.getSelectedCountryData();
                        if (!countryData || !countryData.dialCode) return value;
                        value = value.trim();
                        if (value.startsWith('+' + countryData.dialCode)) {
                            value = value.slice(1 + countryData.dialCode.length).trim();
                        } else if (value.startsWith('+')) {
                            value = value.slice(1).trim();
                        }
                        return value;
                    }

                    // Format the current input value as a national phone number, preserving cursor
                    function formatPhoneNumber() {
                        if (typeof intlTelInputUtils === 'undefined') return;
                        const countryData = iti.getSelectedCountryData();
                        if (!countryData || !countryData.iso2) return;
                        const raw = phoneInput.value;
                        const digits = raw.replace(/\D/g, '');
                        if (!digits) { phoneInput.value = ''; return; }
                        try {
                            const cursorPos = phoneInput.selectionStart;
                            const digitsBeforeCursor = raw.slice(0, cursorPos).replace(/\D/g, '').length;
                            const formatted = intlTelInputUtils.formatNumber(
                                digits, countryData.iso2, intlTelInputUtils.numberFormat.NATIONAL
                            );
                            if (formatted && formatted !== raw) {
                                phoneInput.value = formatted;
                                // Restore cursor by matching digit count
                                let newPos = formatted.length;
                                let dc = 0;
                                for (let i = 0; i < formatted.length; i++) {
                                    if (/\d/.test(formatted[i])) {
                                        dc++;
                                        if (dc === digitsBeforeCursor) { newPos = i + 1; break; }
                                    }
                                }
                                phoneInput.setSelectionRange(newPos, newPos);
                            }
                        } catch (e) { /* skip formatting on error */ }
                    }

                    // Auto-detect country from a +dialcode number, switch flag, then format
                    let countryDetectTimer = null;
                    function detectAndSetCountry(value) {
                        try {
                            iti.setNumber(value); // sets flag + strips dial code into input
                            const countryData = iti.getSelectedCountryData();
                            if (countryData && countryData.dialCode) {
                                $('#country-code-input').val('+' + countryData.dialCode);
                                $('#region-code-input').val(countryData.iso2 ?? '');
                            }
                            formatPhoneNumber();
                        } catch (e) {
                            phoneInput.value = stripDialCode(value);
                            formatPhoneNumber();
                        }
                    }

                    // Format normally; if starts with +, debounce 400ms to detect country
                    phoneInput.addEventListener('input', function() {
                        clearTimeout(countryDetectTimer);
                        if (this.value.startsWith('+')) {
                            countryDetectTimer = setTimeout(function() {
                                detectAndSetCountry(phoneInput.value.trim());
                            }, 400);
                        } else {
                            formatPhoneNumber();
                        }
                    });

                    // Paste: detect country immediately (no debounce needed)
                    phoneInput.addEventListener('paste', function() {
                        clearTimeout(countryDetectTimer);
                        setTimeout(function() {
                            const val = phoneInput.value.trim();
                            if (val.startsWith('+')) {
                                detectAndSetCountry(val);
                            } else {
                                formatPhoneNumber();
                            }
                        }, 0);
                    });

                    // Update hidden fields, strip dial code, and reformat for new country
                    phoneInput.addEventListener('countrychange', function() {
                        const countryData = iti.getSelectedCountryData();
                        if (countryData && countryData.dialCode) {
                            $('#country-code-input').val('+' + countryData.dialCode);
                            $('#region-code-input').val(countryData.iso2 ?? '');
                        }
                        phoneInput.value = stripDialCode(phoneInput.value);
                        formatPhoneNumber();
                    });

                    // Set initial hidden field values and format pre-filled value after utils load
                    setTimeout(function() {
                        const initialCountryData = iti.getSelectedCountryData();
                        if (initialCountryData && initialCountryData.dialCode) {
                            $('#country-code-input').val('+' + initialCountryData.dialCode);
                            $('#region-code-input').val(initialCountryData.iso2 ?? '');
                        }
                        if (phoneInput.value) {
                            phoneInput.value = stripDialCode(phoneInput.value);
                            formatPhoneNumber();
                        }
                    }, 1000);
                } catch (error) {
                    // Silently handle error
                }
            }
            
            // Initialize when Details tab is shown
            $('#addItemTabs a[href="#listing"]').on('shown.bs.tab', function() {
                setTimeout(initPhoneInput, 300);
            });
            
            // Also initialize on page load if Details tab is already visible
            if ($('#listing').hasClass('active') || $('#listing').hasClass('show')) {
                setTimeout(initPhoneInput, 500);
            } else {
                // Initialize after a short delay to ensure library is loaded
                setTimeout(initPhoneInput, 200);
            }

            // Load initial parent categories when categories tab is shown
            $('#addItemTabs a[href="#categories"]').on('shown.bs.tab', function() {
                if ($('#categories-list').is(':empty')) {
                    loadCategories(null, 1);
                }
            });

            // Load categories immediately if categories tab is already active on page load
            // Check if categories tab is visible/active
            if (($('#categories').hasClass('active') || $('#categories').hasClass('show')) && $('#categories-list')
                .is(':empty')) {
                loadCategories(null, 1);
            }

            // Fallback: Load categories after a short delay if still empty (for edge cases)
            setTimeout(function() {
                if ($('#categories-list').is(':empty') && ($('#categories').is(':visible') || $(
                        '#categories').hasClass('active'))) {
                    loadCategories(null, 1);
                }
            }, 100);

            // Load categories function
            function loadCategories(parentId, page = 1) {
                const url = parentId ?
                    '{{ route('advertisement.get-subcategories') }}' :
                    '{{ route('advertisement.get-parent-categories') }}';

                $.ajax({
                    url: url,
                    type: 'GET',
                    data: {
                        category_id: parentId,
                        page: page,
                        per_page: 10
                    },
                    success: function(response) {
                        if (page === 1) {
                            $('#categories-list').html('');
                        }

                        if (response.data && response.data.length > 0) {
                            let html = '';
                            response.data.forEach(function(category) {
                                const hasSubcategories = category.subcategories_count > 0;
                                const categoryImage = category.image ||
                                    '/assets/images/default-category.png';

                                // Only show arrow if there are subcategories
                                const arrowHtml = hasSubcategories ? 
                                    '<div class="category-arrow ms-2" style="color: #999;"><i class="fas fa-chevron-right"></i></div>' : 
                                    '';

                                html += `
                            <div class="col-md-3 col-lg-3 col-xl-3 mb-4">
                                <div class="category-card d-flex align-items-center p-3" 
                                     data-category-id="${category.id}" 
                                     data-category-name="${category.name}"
                                     data-has-subcategories="${hasSubcategories}"
                                     style="cursor: pointer; border: 1px solid #e0e0e0; border-radius: 8px; transition: all 0.3s; background: white; height: 80px;">
                                    <div class="category-icon me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                        <img src="${categoryImage}" alt="${category.name}" class="img-fluid" style="max-height: 50px; max-width: 50px; object-fit: contain;">
                                    </div>
                                    <div class="category-name flex-grow-1" style="font-size: 14px; font-weight: 500; color: #333;">
                                        ${category.name}
                                    </div>
                                    ${arrowHtml}
                                </div>
                            </div>
                        `;
                            });
                            $('#categories-list').append(html);

                            // Add click handlers
                            $('.category-card').off('click').on('click', function() {
                                const categoryId = $(this).data('category-id');
                                const categoryName = $(this).data('category-name');
                                const hasSubcategories = $(this).data('has-subcategories') == 1 || $(this).data('has-subcategories') === true;

                                // Visual feedback - remove previous selections
                                $('.category-card').css({
                                    'border-color': '#e0e0e0',
                                    'background-color': 'white',
                                    'box-shadow': 'none'
                                });

                                // Highlight selected
                                $(this).css({
                                    'border-color': '#20c997',
                                    'background-color': '#f0fdfa',
                                    'box-shadow': '0 2px 4px rgba(32, 201, 151, 0.2)'
                                });

                                if (hasSubcategories) {
                                    // Navigate to subcategories
                                    categoryHistory.push({
                                        parentId: currentParentId,
                                        page: currentPage,
                                        html: $('#categories-list').html(),
                                        loadMoreVisible: $('#load-more-container').is(
                                            ':visible'),
                                        categoryPath: [...selectedCategoryPath]
                                    });
                                    selectedCategoryPath.push(categoryName);
                                    currentParentId = categoryId;
                                    currentPage = 1;
                                    hasMoreCategories = true;
                                    updateBreadcrumb(categoryName, categoryId);
                                    loadCategories(categoryId, 1);
                                } else {
                                    // Select this category (no subcategories)
                                    // Build the full category path
                                    const fullPath = [...selectedCategoryPath, categoryName];
                                    selectCategory(categoryId, fullPath.join(', '));
                                }
                            });

                            // Show/hide load more button
                            hasMoreCategories = response.has_more || false;
                            if (hasMoreCategories && page === 1) {
                                $('#load-more-container').show();
                            } else if (!hasMoreCategories) {
                                $('#load-more-container').hide();
                            }

                            currentPage = page;
                        } else {
                            if (page === 1) {
                                $('#categories-list').html(
                                    '<div class="col-12"><p class="text-muted text-center">No categories found.</p></div>'
                                );
                            }
                            $('#load-more-container').hide();
                            hasMoreCategories = false;
                        }
                    },
                    error: function() {
                        $('#categories-list').html(
                            '<div class="col-12"><p class="text-danger text-center">Error loading categories.</p></div>'
                        );
                    }
                });
            }

            // Load more categories
            $('#load-more-categories').on('click', function() {
                if (hasMoreCategories) {
                    loadCategories(currentParentId, currentPage + 1);
                }
            });
            
            // Go back button handler
            $('#go-back-category-btn').on('click', function() {
                if (categoryHistory.length > 0) {
                    const previous = categoryHistory[categoryHistory.length - 1];
                    goBackToCategory(previous.parentId);
                } else {
                    goBackToCategory(null);
                }
            });

            // Select category function
            function selectCategory(categoryId, categoryName) {
                if (!categoryId) {
                    return;
                }
                
                selectedCategoryId = categoryId;
                selectedCategoryName = categoryName;
                
                // Set the hidden input field
                const categoryInput = $('#category_id');
                if (categoryInput.length) {
                    categoryInput.val(categoryId);
                }
                
                // Update selected category display in Details tab
                updateSelectedCategoryDisplay(categoryName);

                // Load custom fields in the "Other Details" tab
                loadCustomFields(categoryId);

                // Unlock all tabs once category selected
                $('#addItemTabs a[data-bs-toggle="tab"]').removeClass('tab-disabled');

                // Auto-jump to Listing Details
                setTimeout(() => {
                    $('[href="#listing"]').tab('show');
                }, 100);
            }

            // Update selected category display
            function updateSelectedCategoryDisplay(categoryPath) {
                $('#selected-category-breadcrumb').text(categoryPath);
            }

            // Update breadcrumb
            function updateBreadcrumb(categoryName, categoryId) {
                $('#breadcrumb-container').show();
                $('#go-back-category-btn').show();
                let breadcrumbHtml =
                    '<li class="breadcrumb-item"><a href="javascript:void(0)" class="breadcrumb-link" data-parent-id="null">Home</a></li>';

                // Add current category to breadcrumb
                breadcrumbHtml += `<li class="breadcrumb-item active">${categoryName}</li>`;

                $('#category-breadcrumb').html(breadcrumbHtml);

                // Add click handler for breadcrumb
                $('.breadcrumb-link').off('click').on('click', function() {
                    const parentId = $(this).data('parent-id') === 'null' ? null : $(this).data(
                        'parent-id');
                    goBackToCategory(parentId);
                });
            }

            // Go back to previous category level
            function goBackToCategory(parentId) {
                if (categoryHistory.length > 0) {
                    const previous = categoryHistory.pop();
                    currentParentId = previous.parentId;
                    currentPage = previous.page;
                    selectedCategoryPath = previous.categoryPath || [];
                    $('#categories-list').html(previous.html);

                    if (previous.loadMoreVisible) {
                        $('#load-more-container').show();
                    } else {
                        $('#load-more-container').hide();
                    }

                    if (currentParentId === null) {
                        $('#breadcrumb-container').hide();
                        $('#go-back-category-btn').hide();
                        selectedCategoryPath = [];
                    } else {
                        // Update breadcrumb
                        updateBreadcrumbForBack();
                        $('#go-back-category-btn').show();
                    }

                    // Re-attach click handlers
                    $('.category-card').off('click').on('click', function() {
                        const categoryId = $(this).data('category-id');
                        const categoryName = $(this).data('category-name');
                        const hasSubcategories = $(this).data('has-subcategories') == 1 || $(this).data('has-subcategories') === true;

                        // Visual feedback - remove previous selections
                        $('.category-card').css({
                            'border-color': '#e0e0e0',
                            'background-color': 'white',
                            'box-shadow': 'none'
                        });

                        // Highlight selected
                        $(this).css({
                            'border-color': '#20c997',
                            'background-color': '#f0fdfa',
                            'box-shadow': '0 2px 4px rgba(32, 201, 151, 0.2)'
                        });

                        if (hasSubcategories) {
                            categoryHistory.push({
                                parentId: currentParentId,
                                page: currentPage,
                                html: $('#categories-list').html(),
                                loadMoreVisible: $('#load-more-container').is(':visible'),
                                categoryPath: [...selectedCategoryPath]
                            });
                            selectedCategoryPath.push(categoryName);
                            currentParentId = categoryId;
                            currentPage = 1;
                            hasMoreCategories = true;
                            updateBreadcrumb(categoryName, categoryId);
                            loadCategories(categoryId, 1);
                        } else {
                            // Build the full category path
                            const fullPath = [...selectedCategoryPath, categoryName];
                            selectCategory(categoryId, fullPath.join(', '));
                        }
                    });
                } else {
                    // Go to root
                    currentParentId = null;
                    currentPage = 1;
                    categoryHistory = [];
                    selectedCategoryPath = [];
                    $('#breadcrumb-container').hide();
                    $('#go-back-category-btn').hide();
                    loadCategories(null, 1);
                }
            }

            function updateBreadcrumbForBack() {
                // Simplified breadcrumb for back navigation
                $('#breadcrumb-container').show();
                let breadcrumbHtml =
                    '<li class="breadcrumb-item"><a href="javascript:void(0)" class="breadcrumb-link" data-parent-id="null">Home</a></li>';
                $('#category-breadcrumb').html(breadcrumbHtml);

                $('.breadcrumb-link').off('click').on('click', function() {
                    goBackToCategory(null);
                });
            }


            // Function to load custom fields in the "Other Details" tab
            function loadCustomFields(categoryId) {
                if (!categoryId) {
                    $('#custom').html(
                        '<div class="text-muted">{{ __('Select a category to load custom fields.') }}</div>');
                    $('#custom-tab-item').hide();
                    return;
                }

                $.ajax({
                    url: `/get-custom-fields/${categoryId}`,
                    type: 'GET',
                    success: function(response) {
                        let html = '';
                        
                        // Add language selector for custom fields
                        html += `<div class="row mb-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">{{ __('Extra Details') }}</h5>
                                    <div class="d-flex align-items-center">
                                        <label class="me-2 mb-0">{{ __('Select Language') }}:</label>
                                        <select class="form-control form-control-sm" id="custom-fields-language-selector" style="width: 200px;">
                                            @foreach ($languages as $lang)
                                                <option value="{{ $lang->id }}" data-code="{{ $lang->code }}" {{ $lang->id == $defaultLanguage->id ? 'selected' : '' }}>
                                                    {{ $lang->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>`;

                        html += `<div class="row">`;

                        if (response.fields.length === 0) {
                            html += `<p class="text-muted">No custom fields for this category.</p>`;
                            html += `<div class="mt-4 d-flex justify-content-between">
                        <button type="button" class="btn btn-primary btn-prev-tab" data-prev-tab="listing">{{ __('Previous') }}</button>
                        <button type="button" class="btn btn-primary btn-next-tab" data-next-tab="images">{{ __('Next') }}</button>
                    </div>`;
                            $('#custom-tab-item').hide();
                            hasCustomFields = false;
                            $('[href="#custom"]').css('pointer-events', 'none').css('cursor', 'not-allowed');
                        } else {
                            $('#custom-tab-item').show();
                            hasCustomFields = true;
                            $('[href="#custom"]').css('pointer-events', 'auto').css('cursor', 'pointer');
                            
                            // Filter translatable fields (fields that have translations)
                            const translatableFields = response.fields.filter(field => field.has_translations || field.translations_count > 0);
                            
                            // Default language - show all fields
                            html += `<div class="col-12 custom-fields-language-section" data-language-id="${defaultLanguageId}"><div class="row">`;
                            
                            response.fields.forEach(function(field) {
                                const isRequired = field.required ? 'required' : '';
                                html += `<div class="col-md-6 mb-3">`;
                                html += `<label>${field.name}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>`;

                                if (field.type === 'textbox') {
                                    html += `<input type="text" name="custom_fields[${field.id}]" class="form-control custom-field-input" ${isRequired}>`;
                                } else if (field.type === 'number') {
                                    html += `<input type="number" name="custom_fields[${field.id}]" class="form-control custom-field-input" ${isRequired}>`;
                                } else if (field.type === 'fileinput') {
                                    html += `<input type="file" name="custom_field_files[${field.id}]" class="form-control custom-field-input" ${isRequired}>`;
                                } else if (field.type === 'dropdown' || field.type === 'radio') {
                                    const options = Array.isArray(field.values) ? field.values : JSON.parse(field.values ?? '[]');
                                    html += `<select name="custom_fields[${field.id}]" class="form-select custom-field-input" ${isRequired}>`;
                                    html += `<option value="">Select</option>`;
                                    options.forEach(option => {
                                        html += `<option value="${option}">${option}</option>`;
                                    });
                                    html += `</select>`;
                                } else if (field.type === 'checkbox') {
                                    const options = Array.isArray(field.values) ? field.values : JSON.parse(field.values ?? '[]');
                                    options.forEach(option => {
                                        html += `
                                    <div class="form-check">
                                        <input class="form-check-input custom-field-checkbox" type="checkbox" name="custom_fields[${field.id}][]" value="${option}" ${isRequired}>
                                        <label class="form-check-label">${option}</label>
                                    </div>
                                `;
                                    });
                                }
                                html += `</div>`;
                            });
                            
                            html += `</div></div>`;
                            
                            // Other language fields - only show translatable fields
                            languages.forEach(function(lang) {
                                if (lang.id != defaultLanguageId) {
                                    html += `<div class="col-12 custom-fields-language-section" data-language-id="${lang.id}" style="display: none;">`;
                                    html += `<div class="row">`;
                                    
                                    // Show all textbox type fields for other languages (regardless of whether they have translations)
                                    response.fields.forEach(function(field) {
                                        // Only show textbox type fields for other languages
                                        if (field.type !== 'textbox') return;
                                        
                                        // Translation fields are NOT required - only default language fields are required
                                        html += `<div class="col-md-6 mb-3">`;
                                        html += `<label>${field.name}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>`;
                                        html += `<input type="text" name="custom_field_translations[${lang.id}][${field.id}]" class="form-control custom-field-input-translation">`;
                                html += `</div>`;
                            });
                                    
                                    html += `</div></div>`;
                                }
                            });
                        }

                        html += `</div>`;

                            // Add navigation buttons for custom fields
                            html += `<div class="mt-4 d-flex justify-content-between">
                        <button type="button" class="btn btn-primary btn-prev-tab" data-prev-tab="listing">{{ __('Previous') }}</button>
                        <button type="button" class="btn btn-primary btn-next-tab" data-next-tab="images">{{ __('Next') }}</button>
                    </div>`;

                        $('#custom').html(html);
                        updateCustomFieldsStatus();
                        
                        // Language switching for custom fields
                        $('#custom-fields-language-selector').off('change').on('change', function() {
                            const selectedLangId = $(this).val();
                            
                            // Hide all language sections and remove required from hidden fields
                            $('.custom-fields-language-section').each(function() {
                                $(this).hide();
                                // Remove required attribute from all inputs in hidden sections
                                $(this).find('input[required], select[required], textarea[required]').each(function() {
                                    $(this).data('was-required', true);
                                    $(this).removeAttr('required');
                                });
                            });
                            
                            // Show selected language section and restore required attributes
                            const $selectedSection = $(`.custom-fields-language-section[data-language-id="${selectedLangId}"]`);
                            $selectedSection.show();
                            // Restore required for fields that were marked
                            $selectedSection.find('input[data-was-required="true"], select[data-was-required="true"], textarea[data-was-required="true"]').each(function() {
                                $(this).attr('required', 'required');
                            });
                            // Add required for fields that should be required (newly created fields)
                            $selectedSection.find('input[data-should-be-required="true"], select[data-should-be-required="true"], textarea[data-should-be-required="true"]').each(function() {
                                $(this).attr('required', 'required');
                            });
                        });
                        
                        // Initialize: Show default language fields and mark required fields
                        const defaultCustomLangId = $('#custom-fields-language-selector').val();
                        // Hide all sections first
                        $('.custom-fields-language-section').each(function() {
                            $(this).hide();
                            // Mark required fields in hidden sections
                            $(this).find('input[required], select[required], textarea[required]').each(function() {
                                $(this).data('was-required', true);
                                $(this).removeAttr('required');
                            });
                        });
                        
                        // Show default language section and restore required
                        const $defaultSection = $(`.custom-fields-language-section[data-language-id="${defaultCustomLangId}"]`);
                        $defaultSection.show();
                        // Restore required for fields that were marked
                        $defaultSection.find('input[data-was-required="true"], select[data-was-required="true"], textarea[data-was-required="true"]').each(function() {
                            $(this).attr('required', 'required');
                        });
                        // Add required for fields that should be required (newly created fields)
                        $defaultSection.find('input[data-should-be-required="true"], select[data-should-be-required="true"], textarea[data-should-be-required="true"]').each(function() {
                            $(this).attr('required', 'required');
                        });

                        // Handle job category and price optional logic
                        const isJobCategory = response.is_job_category;
                        const isPriceOptional = response.price_optional;
                        
                        if (isJobCategory) {
                            // Job category: show salary fields, hide price
                            $('#price-field').hide();
                            $('#price-input').removeAttr('required');
                            $('#salary-fields').show();
                            
                            // Update salary field labels and requirements
                            if (isPriceOptional) {
                                // Both job category AND price optional: salary is optional
                                $('#salary-fields label').each(function() {
                                    $(this).html($(this).html().replace(' <span class="text-danger">*</span>', ''));
                                });
                                $('#min-salary-input').removeAttr('required');
                                $('#max-salary-input').removeAttr('required');
                            } else {
                                // Job category but price not optional: salary is required
                                $('#salary-fields label').first().html('{{ __('Min Salary') }} <span class="text-danger">*</span>');
                                $('#salary-fields label').last().html('{{ __('Max Salary') }} <span class="text-danger">*</span>');
                                $('#min-salary-input').attr('required', 'required');
                                $('#max-salary-input').attr('required', 'required');
                            }
                        } else {
                            // Not a job category: show price field, hide salary
                            $('#price-field').show();
                            $('#salary-fields').hide();
                            
                            if (isPriceOptional) {
                                // Price optional: remove required
                                $('#price-field label').html('{{ __('Price') }}');
                                $('#price-input').removeAttr('required');
                            } else {
                                // Price not optional: make required
                                $('#price-field label').html('{{ __('Price') }} <span class="text-danger">*</span>');
                                $('#price-input').attr('required', 'required');
                            }
                        }
                    },
                    error: function() {
                        $('#custom').html(
                            '<div class="text-danger">Error loading custom fields.</div>');
                        $('#custom-tab-item').hide();
                    }
                });
            }

            // Tab navigation with validation
            let hasCustomFields = false;

            // Update hasCustomFields when custom fields are loaded
            function updateCustomFieldsStatus() {
                hasCustomFields = $('#custom .custom-field-input').length > 0;
            }

            // Disable all tab links except categories (first tab)
            $('#addItemTabs a[data-bs-toggle="tab"]').each(function() {
                const href = $(this).attr('href');
                if (href !== '#categories') {
                    $(this).addClass('tab-disabled');
                }
            });

            // Prevent tab switching only if category not selected yet
            $(document).on('click', '#addItemTabs a[data-bs-toggle="tab"]', function(e) {
                const targetTab = $(this).attr('href');
                if (targetTab === '#categories') return true;

                const categoryIdValue = $('#category_id').val();
                if (!selectedCategoryId || !categoryIdValue) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    showErrorToast(window.trans('Please select a category first.'));
                    return false;
                }
            });

            $('#addItemTabs a[data-bs-toggle="tab"]').on('show.bs.tab', function(e) {
                const targetTab = $(e.target).attr('href');
                if (targetTab === '#categories') return true;

                const categoryIdValue = $('#category_id').val();
                if (!selectedCategoryId || !categoryIdValue) {
                    e.preventDefault();
                    return false;
                }
            });

            // Next button handler
            $(document).on('click', '.btn-next-tab', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const nextTab = $(this).data('next-tab');
                const currentTab = $('.tab-pane.active').attr('id');

                if (!validateCurrentTab(currentTab)) {
                    return false;
                }

                if (nextTab === 'custom-or-images') {
                    if (hasCustomFields) {
                        $('[href="#custom"]').tab('show');
                    } else {
                        $('[href="#images"]').tab('show');
                    }
                } else {
                    $('[href="#' + nextTab + '"]').tab('show');
                    if (nextTab === 'address') {
                        setTimeout(() => { initMap(); }, 300);
                    }
                }
            });

            // Previous button handler
            $(document).on('click', '.btn-prev-tab', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const prevTab = $(this).data('prev-tab');

                if (prevTab === 'custom-or-images') {
                    if (hasCustomFields) {
                        $('[href="#custom"]').tab('show');
                    } else {
                        $('[href="#listing"]').tab('show');
                    }
                } else {
                    $('[href="#' + prevTab + '"]').tab('show');
                }
            });

            // Init map when address tab shown via direct click
            $('#addItemTabs a[href="#address"]').on('shown.bs.tab', function() {
                setTimeout(() => { initMap(); }, 300);
            });

            // Validation function for current tab
            function validateCurrentTab(tabId) {
                let isValid = true;
                let firstInvalidField = null;

                if (tabId === 'categories') {
                    const categoryIdValue = $('#category_id').val();
                    if (!selectedCategoryId || !categoryIdValue || categoryIdValue === '') {
                        showErrorToast(window.trans('Please select a category first.'));
                        isValid = false;
                    }
                } else if (tabId === 'listing') {
                    const name = $('#name-input').val().trim();
                    const description = $('#description-input').val().trim();
                    const price = $('#price-input').val();
                    const minSalary = $('#min-salary-input').val();
                    const maxSalary = $('#max-salary-input').val();
                    const contact = $('#contact-input').val().trim();

                    if (!name) {
                        showErrorToast(window.trans('Please enter a english title.'));
                        $('#name-input').focus();
                        isValid = false;
                    } else if (!description) {
                        showErrorToast(window.trans('Please enter a english description.'));
                        $('#description-input').focus();
                        isValid = false;
                    } else if ($('#price-field').css('display') !== 'none' && $('#price-input').attr('required') && !price) {
                        showErrorToast(window.trans('Please enter a price.'));
                        $('#price-input').focus();
                        isValid = false;
                    } else if ($('#salary-fields').css('display') !== 'none') {
                        const minSalaryRequired = $('#min-salary-input').attr('required');
                        const maxSalaryRequired = $('#max-salary-input').attr('required');
                        
                        if (minSalaryRequired && !minSalary) {
                            showErrorToast(window.trans('Please enter a minimum salary.'));
                            $('#min-salary-input').focus();
                            isValid = false;
                        } else if (maxSalaryRequired && !maxSalary) {
                            showErrorToast(window.trans('Please enter a maximum salary.'));
                            $('#max-salary-input').focus();
                            isValid = false;
                        } else if (minSalary && maxSalary && parseFloat(minSalary) > parseFloat(maxSalary)) {
                            showErrorToast(window.trans('Min salary cannot be greater than max salary.'));
                            $('#min-salary-input').focus();
                            isValid = false;
                        }
                    }

                    // Phone number validation
                    if (isValid && contact && phoneIti) {
                        try {
                            if (typeof intlTelInputUtils !== 'undefined' && !phoneIti.isValidNumber()) {
                                showErrorToast(window.trans('Please enter a valid phone number for the selected country.'));
                                $('#contact-input').focus();
                                isValid = false;
                            }
                        } catch (e) {
                            // Skip validation if utils not loaded
                        }
                    }

                } else if (tabId === 'custom') {
                    // Validate required custom fields
                    let firstInvalidField = null;
                    $('#custom .custom-field-input[required]').each(function() {
                        const $field = $(this);
                        if ($field.is(':file')) {
                            if (!$field[0].files || $field[0].files.length === 0) {
                                if (!firstInvalidField) {
                                    firstInvalidField = $field;
                                }
                                isValid = false;
                                return false;
                            }
                        } else if ($field.is('select')) {
                            if (!$field.val()) {
                                if (!firstInvalidField) {
                                    firstInvalidField = $field;
                                }
                                isValid = false;
                                return false;
                            }
                        } else if (!$field.val().trim()) {
                            if (!firstInvalidField) {
                                firstInvalidField = $field;
                            }
                            isValid = false;
                            return false;
                        }
                    });

                    if (!isValid && firstInvalidField) {
                        showErrorToast(window.trans('Please fill all required custom fields.'));
                        firstInvalidField.focus();
                    }

                    // Validate checkbox groups
                    $('.custom-field-checkbox[required]').each(function() {
                        const name = $(this).attr('name');
                        if (!$('input[name="' + name + '"]:checked').length) {
                            showErrorToast(window.trans('Please select at least one option for required checkbox fields.'));
                            isValid = false;
                            return false;
                        }
                    });
                } else if (tabId === 'images') {
                    const galleryInput = $('#gallery-images-input')[0];
                    if (!galleryInput || !galleryInput.files || galleryInput.files.length === 0) {
                        showErrorToast(window.trans('Please select at least one product image.'));
                        $('#gallery-images-input').focus();
                        isValid = false;
                    }
                    
                    // Validate video link if provided
                    const videoLink = $('#video_link').val().trim();
                    if (videoLink && !isValidUrl(videoLink)) {
                        showErrorToast(window.trans('Please enter a valid video URL (e.g., https://www.youtube.com/watch?v=...) or leave it empty.'));
                        $('#video_link').addClass('is-invalid').focus();
                        isValid = false;
                    } else if (videoLink && isValidUrl(videoLink)) {
                        $('#video_link').removeClass('is-invalid');
                    }
                } else if (tabId === 'address') {
                    const lat = $('#latitude-input').val();
                    const lng = $('#longitude-input').val();

                    if (!lat || !lng) {
                        showErrorToast(window.trans('Please select a location on the map.'));
                        isValid = false;
                    }
                }

                return isValid;
            }

            // URL validation helper
            function isValidUrl(string) {
                if (!string || !string.trim()) return true; // Empty is valid (nullable)
                try {
                    const url = new URL(string);
                    return url.protocol === 'http:' || url.protocol === 'https:';
                } catch (_) {
                    return false;
                }
            }

            // Pre-submit validation function (called by global form handler)
            window.validateAdvertisementForm = function() {
                const tabs = ['categories', 'listing', 'images', 'address'];
                if (hasCustomFields) {
                    tabs.splice(2, 0, 'custom');
                }

                for (let i = 0; i < tabs.length; i++) {
                    if (!validateCurrentTab(tabs[i])) {
                        $('[href="#' + tabs[i] + '"]').tab('show');
                        if (tabs[i] === 'address') {
                            setTimeout(() => { initMap(); }, 300);
                        }
                        return false; // Prevent form submission
                    }
                }

                // Normalize contact: send digits-only national number to backend
                // If no phone number entered, clear country_code and region_code so they aren't passed
                if (phoneIti) {
                    try {
                        const rawContact = $('#contact-input').val().trim();
                        if (rawContact) {
                            let nationalDigits = rawContact.replace(/\D/g, '');
                            $('#contact-input').val(nationalDigits);
                        } else {
                            $('#country-code-input').val('');
                            $('#region-code-input').val('');
                        }
                    } catch (e) {
                        // Keep original value on error
                    }
                }

                return true; // Allow form submission
            };

            // Email validation helper
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            // Toggle password visibility for user details
            // $('#toggle-user-password-visibility').on('click', function() {
            //     const passwordInput = $('#user-password-input');
            //     const eyeIcon = $('#user-password-eye-icon');

            //     if (passwordInput.attr('type') === 'password') {
            //         passwordInput.attr('type', 'text');
            //         eyeIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            //     } else {
            //         passwordInput.attr('type', 'password');
            //         eyeIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            //     }
            // });
        });

        // Country/State/City dropdown handlers - kept for map address population
        $(document).ready(function() {
            // These handlers are now only used for populating dropdowns from map selection
            // No manual dropdowns are shown in the UI anymore
        });

        // Map initialization using API endpoint
        let map, marker;
        let mapInitialized = false;
        let autocompleteService;
        let placesService;
        let sessionToken;
        let searchTimeout;

        function initMap() {
            // Check if map is already initialized
            if (mapInitialized && map) {
                setTimeout(() => {
                    if (map && typeof map.invalidateSize === 'function') {
                    map.invalidateSize();
                    }
                }, 100);
                return;
            }

            // Check if map container exists
            const mapContainer = document.getElementById('map');
            if (!mapContainer) {
                return;
            }

            const defaultLat = 20.5937;
            const defaultLng = 78.9629;

            try {
                // Initialize Leaflet map as fallback
                map = L.map('map').setView([defaultLat, defaultLng], 5);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map);

                marker = L.marker([defaultLat, defaultLng], {
                    draggable: true
                }).addTo(map);

                updateLatLngInputs(defaultLat, defaultLng);
                fetchAddressFromCoords(defaultLat, defaultLng);

                // Handle marker drag
                marker.on('dragend', function(e) {
                    const pos = marker.getLatLng();
                    updateLatLngInputs(pos.lat, pos.lng);
                    fetchAddressFromCoords(pos.lat, pos.lng);
                });

                // Handle map click
                map.on('click', function(e) {
                    const lat = e.latlng.lat;
                    const lng = e.latlng.lng;
                    marker.setLatLng([lat, lng]);
                    updateLatLngInputs(lat, lng);
                    fetchAddressFromCoords(lat, lng);
                });

                mapInitialized = true;
            } catch (error) {
                alert('Map initialization failed: ' + error.message);
            }
        }


        function updateLatLngInputs(lat, lng) {
            document.getElementById("latitude-input").value = lat;
            document.getElementById("longitude-input").value = lng;
        }

        // Location search functionality
        function searchLocation(query) {
            if (!query || query.length < 3) {
                $('#search-results').hide();
                return;
            }
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: '{{ url("api/get-location") }}',
                    type: 'GET',
                    data: {
                        search: query,
                        lang: '{{ app()->getLocale() }}'
                    },
                    headers: {
                        'Content-Language': '{{ app()->getLocale() }}'
                    },
                    success: function(response) {
                        
                        // Handle different response structures
                        let searchData = null;
                        
                        if (response && response.status && response.data) {
                            searchData = response.data;
                        } else if (response && Array.isArray(response)) {
                            searchData = response;
                        } else if (response && response.data) {
                            searchData = response.data;
                        }
                        
                        
                        if (searchData) {
                            displaySearchResults(searchData);
                        } else {
                            $('#search-results').hide();
                        }
                    },
                    error: function(error) {
                        $('#search-results').hide();
                    }
                });
            }, 500);
        }
        
        function displaySearchResults(data) {
            const resultsContainer = $('#search-results');
            resultsContainer.empty();

            let hasResults = false;

            // Handle Google Places API autocomplete response
            if (data && data.predictions && Array.isArray(data.predictions) && data.predictions.length > 0) {
                data.predictions.forEach(function(prediction) {
                    const item = $('<div class="p-2 border-bottom search-result-item" style="cursor: pointer; transition: background 0.2s;"></div>');
                    item.html('<i class="fas fa-map-marker-alt text-primary me-2"></i>' + (prediction.description || prediction.name || ''));
                    item.on('click', function(e) {
                        e.stopPropagation();
                        if (prediction.place_id) {
                            selectLocationFromSearch(prediction.place_id);
                        } else if (prediction.latitude && prediction.longitude) {
                            selectLocationFromCoords(prediction.latitude, prediction.longitude);
                        }
                        resultsContainer.hide();
                        $('#location-search').val(prediction.description || prediction.name || '');
                    });
                    item.on('mouseenter', function() {
                        $(this).css('background', '#f5f5f5');
                    });
                    item.on('mouseleave', function() {
                        $(this).css('background', 'white');
                    });
                    resultsContainer.append(item);
                    hasResults = true;
                });
            }
            // Handle local database results (array of cities/areas)
            else if (data && Array.isArray(data) && data.length > 0) {
                data.forEach(function(location) {
                    const cityName = location.city_translation || location.city || '';
                    const stateName = location.state_translation || location.state || '';
                    const countryName = location.country_translation || location.country || '';
                    const areaName = location.area_translation || location.area || '';

                    const address = [areaName, cityName, stateName, countryName].filter(Boolean).join(', ');
                    if (!address) return; // Skip if no address

                    // Check for latitude/longitude in different possible locations
                    const lat = location.latitude || location.lat;
                    const lng = location.longitude || location.lng;
                    

                    const item = $('<div class="p-2 border-bottom search-result-item" style="cursor: pointer; transition: background 0.2s;"></div>');
                    item.html('<i class="fas fa-map-marker-alt text-primary me-2"></i>' + address);
                    item.on('click', function(e) {
                        e.stopPropagation();
                        if (lat && lng) {
                            selectLocationFromCoords(lat, lng);
                            resultsContainer.hide();
                            $('#location-search').val(address);
                        } else {
                            alert('No coordinates available for this location');
                        }
                    });
                    item.on('mouseenter', function() {
                        $(this).css('background', '#f5f5f5');
                    });
                    item.on('mouseleave', function() {
                        $(this).css('background', 'white');
                    });
                    resultsContainer.append(item);
                    hasResults = true;
                });
            }

            if (hasResults) {
                resultsContainer.show();
            } else {
                resultsContainer.hide();
            }
        }
        
        function selectLocationFromSearch(placeId) {
            $.ajax({
                url: '{{ url("api/get-location") }}',
                type: 'GET',
                data: {
                    place_id: placeId,
                    lang: '{{ app()->getLocale() }}'
                },
                headers: {
                    'Content-Language': '{{ app()->getLocale() }}'
                },
                success: function(response) {
                    if (response && (response.error === false || !response.error) && response.data) {
                        const data = response.data;
                        if (data.results && data.results[0] && data.results[0].geometry && data.results[0].geometry.location) {
                            const lat = data.results[0].geometry.location.lat;
                            const lng = data.results[0].geometry.location.lng;
                            selectLocationFromCoords(lat, lng);
                        }
                    } else if (response && response.results && response.results[0] && response.results[0].geometry) {
                        // Fallback for direct Google API response
                        const lat = response.results[0].geometry.location.lat;
                        const lng = response.results[0].geometry.location.lng;
                        selectLocationFromCoords(lat, lng);
                    }
                },
                error: function(error) {
                    // Silently handle error
                }
            });
        }
        
        function selectLocationFromCoords(lat, lng) {

            // Initialize map if not already initialized
            if (!map || !mapInitialized) {
                initMap();
                // Wait for map to be initialized
                setTimeout(function() {
                    updateMapLocation(lat, lng);
                }, 300);
            } else {
                updateMapLocation(lat, lng);
            }
        }

        function updateMapLocation(lat, lng) {
            if (map && marker) {
                map.setView([lat, lng], 13);
                marker.setLatLng([lat, lng]);
                updateLatLngInputs(lat, lng);
                fetchAddressFromCoords(lat, lng);
            } else {
            }
        }
        
        // Locate me functionality
        function locateUser() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        selectLocationFromCoords(lat, lng);
                    },
                    function(error) {
                        alert('Unable to get your location. Please select manually on the map.');
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        }

        function fetchAddressFromCoords(lat, lng) {
            $.ajax({
                url: '{{ url("api/get-location") }}',
                type: 'GET',
                data: {
                    lat: lat,
                    lng: lng,
                    lang: '{{ app()->getLocale() }}'
                },
                headers: {
                    'Content-Language': '{{ app()->getLocale() }}'
                },
                success: function(response) {
                    let fullAddressText = '';
                    let countryName = '';
                    let stateName = '';
                    let cityName = '';
                    
                    // Handle different response formats
                    // Check for error: false or status: success
                    if (response && response.data && (response.error === false || !response.error || response.status === 'success')) {
                        const data = response.data;
                        
                        // Handle Google Places API response (data.results array)
                        if (data.results && Array.isArray(data.results) && data.results.length > 0) {
                            const result = data.results[0];
                            fullAddressText = result.formatted_address || '';
                            
                            // Extract address components
                            if (result.address_components && Array.isArray(result.address_components)) {
                                result.address_components.forEach(function(component) {
                                    if (component.types && Array.isArray(component.types)) {
                                        if (component.types.includes('country')) {
                                            countryName = component.long_name || component.short_name || '';
                                        }
                                        if (component.types.includes('administrative_area_level_1')) {
                                            stateName = component.long_name || component.short_name || '';
                                        }
                                        if (component.types.includes('locality') || component.types.includes('administrative_area_level_2')) {
                                            if (!cityName) { // Prefer locality over administrative_area_level_2
                                                cityName = component.long_name || component.short_name || '';
                                            }
                                        }
                                    }
                                });
                            }
                        } 
                        // Handle local database response (direct data object with city, state, country)
                        else if (data && (data.city || data.city_translation)) {
                            // Build from components - prefer translations if available
                            const area = data.area_translation || data.area || '';
                            const city = data.city_translation || data.city || '';
                            const state = data.state_translation || data.state || '';
                            const country = data.country_translation || data.country || '';
                            
                            // Build address string
                            fullAddressText = [area, city, state, country].filter(Boolean).join(', ');
                            
                            // Use translations if available, otherwise use original
                            countryName = data.country_translation || data.country || '';
                            stateName = data.state_translation || data.state || '';
                            cityName = data.city_translation || data.city || '';
                        }
                        // Handle array response (if API returns array directly)
                        else if (Array.isArray(data) && data.length > 0) {
                            const location = data[0];
                            if (location.formatted_address) {
                                fullAddressText = location.formatted_address;
                            } else if (location.address) {
                                fullAddressText = location.address;
                            } else {
                                // Build from components - prefer translations if available
                                const area = location.area_translation || location.area || '';
                                const city = location.city_translation || location.city || '';
                                const state = location.state_translation || location.state || '';
                                const country = location.country_translation || location.country || '';
                                
                                fullAddressText = [area, city, state, country].filter(Boolean).join(', ');
                            }
                            countryName = location.country_translation || location.country || '';
                            stateName = location.state_translation || location.state || '';
                            cityName = location.city_translation || location.city || '';
                        }
                    } 
                    // Handle direct response without status wrapper
                    else if (response && (response.formatted_address || response.address || response.city || response.city_translation)) {
                        if (response.formatted_address) {
                            fullAddressText = response.formatted_address;
                        } else if (response.address) {
                            fullAddressText = response.address;
                        } else {
                            // Build from components - prefer translations if available
                            const area = response.area_translation || response.area || '';
                            const city = response.city_translation || response.city || '';
                            const state = response.state_translation || response.state || '';
                            const country = response.country_translation || response.country || '';
                            
                            fullAddressText = [area, city, state, country].filter(Boolean).join(', ');
                        }
                        countryName = response.country_translation || response.country || '';
                        stateName = response.state_translation || response.state || '';
                        cityName = response.city_translation || response.city || '';
                    }
                    
                    // Only use coordinates as last resort if we really can't get an address
                    if (!fullAddressText || fullAddressText.trim() === '') {
                        fullAddressText = lat + ', ' + lng;
                    }
                    
                    // Set all address fields
                    const addressInput = document.getElementById("address-hidden");
                    if (addressInput) {
                        addressInput.value = fullAddressText;
                    }
                    
                    const countryInput = document.getElementById("country-input");
                    if (countryInput) {
                        countryInput.value = countryName;
                    }
                    
                    const stateInput = document.getElementById("state-input");
                    if (stateInput) {
                        stateInput.value = stateName;
                    }
                    
                    const cityInput = document.getElementById("city-input");
                    if (cityInput) {
                        cityInput.value = cityName;
                    }
                    
                    // Show selected location info
                    if (fullAddressText) {
                        $('#selected-address-display').show();
                        $('#selected-address-text').text(fullAddressText);
                        $('#location-search').val(fullAddressText);
                    }
                },
                error: function(error) {
                    // Try to use reverse geocoding or show coordinates
                    const fallbackAddress = lat + ', ' + lng;
                    const addressInput = document.getElementById("address-hidden");
                    if (addressInput) {
                        addressInput.value = fallbackAddress;
                    }
                    $('#selected-address-display').show();
                    $('#selected-address-text').text('Location: ' + fallbackAddress);
                    $('#location-search').val(fallbackAddress);
                }
                });
        }

        // Initialize map when address tab is shown
        $(document).ready(function() {
            // Listen for when address tab is shown
            $('a[href="#address"]').on('shown.bs.tab', function() {
                setTimeout(() => {
                    initMap();
                }, 300);
            });

            // Also check if address tab is already active on page load
            if ($('#address').hasClass('active') || $('#address').hasClass('show')) {
                setTimeout(() => {
                    initMap();
                }, 500);
            }
            
            // Location search event handlers
            $('#location-search').on('input', function() {
                const query = $(this).val().trim();
                if (query.length >= 3) {
                    searchLocation(query);
                } else {
                    $('#search-results').hide();
                }
            });
            
            // Also handle keyup for better responsiveness
            $('#location-search').on('keyup', function(e) {
                // Don't search on arrow keys, enter, etc.
                if ([37, 38, 39, 40, 13, 27].indexOf(e.keyCode) !== -1) {
                    return;
                }
                const query = $(this).val().trim();
                if (query.length >= 3) {
                    searchLocation(query);
                } else {
                    $('#search-results').hide();
                }
            });
            
            // Hide search results when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#location-search, #search-results').length) {
                    $('#search-results').hide();
                }
            });
            
            // Locate me button
            $('#locate-me-btn').on('click', function() {
                locateUser();
            });
            
            // Image upload handlers
            // Image upload handlers
            
            // Gallery images
            let isClickingGalleryButton = false;
            
            $('#gallery-images-btn').on('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                isClickingGalleryButton = true;
                const fileInput = document.getElementById('gallery-images-input');
                if (fileInput) {
                    fileInput.click();
                }
                setTimeout(function() {
                    isClickingGalleryButton = false;
                }, 100);
            });
            
            $('#gallery-images-upload').on('click', function(e) {
                // Don't trigger if clicking on the button, preview, or if we just clicked the button
                if (isClickingGalleryButton || $(e.target).closest('#gallery-images-btn, #gallery-images-preview, .remove-gallery-image').length > 0) {
                    return;
                }
                // Only trigger if clicking directly on the container or its empty space
                if (e.target === this || $(e.target).hasClass('upload-area')) {
                    const fileInput = document.getElementById('gallery-images-input');
                    if (fileInput) {
                        fileInput.click();
                    }
                }
            });
            
            // Store gallery files for removal and appending
            let galleryFiles = [];
            
            function renderGalleryPreview() {
                const preview = $('#gallery-images-preview');
                preview.empty();
                
                galleryFiles.forEach((file, i) => {
                    const badgeHtml = i === 0 
                        ? `<span class="badge bg-success position-absolute top-0 start-0 m-1" style="z-index: 2;">Main</span>` 
                        : '';
                    const borderStyle = i === 0 ? "border: 2px solid #20c997;" : "border: 1px solid #ddd;";
                    const imgSrc = URL.createObjectURL(file);

                    const col = $('<div class="col-4 col-md-3 mb-2 gallery-image-item" data-file-index="' + i + '"></div>');
                    col.html(`
                        <div class="position-relative" style="border-radius: 8px; overflow: hidden; ${borderStyle}">
                            ${badgeHtml}
                            <img src="${imgSrc}" class="img-fluid" style="aspect-ratio: 1; width: 100%; object-fit: cover; display: block;">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 remove-gallery-image" data-index="${i}" style="border-radius: 50%; width: 24px; height: 24px; padding: 0; line-height: 24px; font-size: 12px; z-index: 2;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `);
                    preview.append(col);
                });
                
                // Update file input
                const input = document.getElementById('gallery-images-input');
                if (input) {
                    const dt = new DataTransfer();
                    galleryFiles.forEach(f => dt.items.add(f));
                    input.files = dt.files;
                }
            }
            
            $('#gallery-images-input').on('change', function(e) {
                const newFiles = Array.from(e.target.files);
                
                if (newFiles.length > 0) {
                    galleryFiles = galleryFiles.concat(newFiles);
                }
                
                renderGalleryPreview();
            });
            
            // Handle gallery image removal
            $(document).on('click', '.remove-gallery-image', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const index = parseInt($(this).data('index'));
                
                galleryFiles.splice(index, 1);
                renderGalleryPreview();
            });
            

            
            // Drag and drop for gallery images
            const galleryUploadArea = document.getElementById('gallery-images-upload');
            if (galleryUploadArea) {
                galleryUploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    $(this).css('border-color', '#20c997');
                    $(this).css('background', '#f0fdfa');
                });
                
                galleryUploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    $(this).css('border-color', '#ddd');
                    $(this).css('background', '#f9f9f9');
                });
                
                galleryUploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    $(this).css('border-color', '#ddd');
                    $(this).css('background', '#f9f9f9');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        const newFiles = [];
                        for (let i = 0; i < files.length; i++) {
                            if (files[i].type.startsWith('image/')) {
                                newFiles.push(files[i]);
                            }
                        }
                        if (newFiles.length > 0) {
                            galleryFiles = galleryFiles.concat(newFiles);
                            renderGalleryPreview();
                        }
                    }
                });
            }

            @if($geminiEnabled ?? false)
            // Gemini AI - Generate Description (per-language aware)
            $('.generate-description-btn').on('click', function() {
                const btn = $(this);
                const spinner = btn.find('.description-loading');
                const $wrap = btn.closest('.language-fields');
                const langId = $wrap.data('language-id');
                const isDefault = $wrap.hasClass('default-language-fields');

                const $title = isDefault
                    ? $('#name-input')
                    : $(`input.translation-name[data-lang-id="${langId}"]`);
                const $desc = isDefault
                    ? $('#description-input')
                    : $(`textarea.translation-description[data-lang-id="${langId}"]`);

                const title = $title.val();
                if (!title) {
                    Toastify({ text: '{{ __("Please enter a title first") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                    return;
                }

                btn.prop('disabled', true);
                spinner.removeClass('d-none');

                $.ajax({
                    url: '{{ route("gemini.generate-description") }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        title: title,
                        location: $('[name="address"]').val() || '',
                        city: $('[name="city"]').val() || $('[name="city"] option:selected').text() || '',
                        state: $('[name="state"]').val() || $('[name="state"] option:selected').text() || '',
                        country: $('[name="country"] option:selected').text() || '',
                        price: $('[name="price"]').val() || '',
                        category_name: $('#selected-category-breadcrumb').text().trim() || '',
                        currency_iso_code: $('#currency option:selected').data('iso-code') || '',
                        language_id: langId
                    },
                    success: function(response) {
                        if (!response.error && response.data) {
                            $desc.val(response.data.description);
                            Toastify({ text: '{{ __("Description generated successfully") }}', duration: 3000, close: true, backgroundColor: 'linear-gradient(to right, #00b09b, #96c93d)' }).showToast();
                        } else {
                            Toastify({ text: response.message || '{{ __("Failed to generate description") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                        }
                    },
                    error: function(xhr) {
                        Toastify({ text: xhr.responseJSON?.message || '{{ __("An error occurred") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });

            // Gemini AI - Generate Meta Details
            $('#generate-meta-btn').on('click', function() {
                const btn = $(this);
                const spinner = $('#meta-loading');
                const selectedSeoLangId = $('#seo-language-selector').val();
                const defaultLangId = {{ $defaultLanguage->id }};
                const isDefaultLang = parseInt(selectedSeoLangId) === defaultLangId;

                const title = isDefaultLang
                    ? $('#name-input').val()
                    : $(`input.translation-name[data-lang-id="${selectedSeoLangId}"]`).val();
                const description = isDefaultLang
                    ? $('#description-input').val()
                    : $(`textarea.translation-description[data-lang-id="${selectedSeoLangId}"]`).val();

                if (!title || !title.trim() || !description || !description.trim()) {
                    Toastify({ text: '{{ __("Please enter title and description for the selected language first") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                    return;
                }

                btn.prop('disabled', true);
                spinner.removeClass('d-none');

                $.ajax({
                    url: '{{ route("gemini.generate-meta") }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        title: title,
                        location: $('[name="address"]').val() || '',
                        city: $('[name="city"]').val() || $('[name="city"] option:selected').text() || '',
                        state: $('[name="state"]').val() || $('[name="state"] option:selected').text() || '',
                        country: $('[name="country"] option:selected').text() || '',
                        price: $('[name="price"]').val() || '',
                        currency_iso_code: $('#currency option:selected').data('iso-code') || '',
                        language_id: selectedSeoLangId
                    },
                    success: function(response) {
                        if (!response.error && response.data) {
                            const langId = selectedSeoLangId;
                            const seoFields = $(`.seo-language-fields[data-seo-language-id="${langId}"]`);
                            seoFields.find(`[name="meta_title[${langId}]"]`).val(response.data.meta_title || '');
                            seoFields.find(`[name="meta_description[${langId}]"]`).val(response.data.meta_description || '');
                            seoFields.find(`[name="meta_keywords[${langId}]"]`).val(response.data.meta_keywords || '');
                            Toastify({ text: '{{ __("SEO details generated successfully") }}', duration: 3000, close: true, backgroundColor: 'linear-gradient(to right, #00b09b, #96c93d)' }).showToast();
                        } else {
                            Toastify({ text: response.message || '{{ __("Failed to generate SEO details") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                        }
                    },
                    error: function(xhr) {
                        Toastify({ text: xhr.responseJSON?.message || '{{ __("An error occurred") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });
            @endif
        });

        @if(($mapProvider ?? 'free_api') === 'free_api')
        // Manual location modal (free_api only)
        $(document).ready(function() {
            const $country = $('#manual-country-select');
            const $state = $('#manual-state-select');
            const $city = $('#manual-city-select');
            const $address = $('#manual-address-input');
            const $modal = $('#manualLocationModal');

            function initSelect2($el, placeholder) {
                if ($el.data('select2')) $el.select2('destroy');
                $el.select2({
                    dropdownParent: $modal,
                    placeholder: placeholder,
                    width: '100%',
                    allowClear: true
                });
            }

            $modal.on('shown.bs.modal', function() {
                initSelect2($country, '{{ __('Country') }}');
                initSelect2($state, '{{ __('State') }}');
                initSelect2($city, '{{ __('City') }}');
            });

            function resetSelect($el, placeholder) {
                $el.empty().append('<option value="">' + placeholder + '</option>').prop('disabled', true);
                initSelect2($el, placeholder);
            }

            $country.on('change', function() {
                const countryId = $(this).val();
                resetSelect($state, '{{ __('State') }}');
                resetSelect($city, '{{ __('City') }}');
                if (!countryId) return;
                $.ajax({
                    url: '{{ url('api/states') }}',
                    data: { country_id: countryId, per_page: 1000 },
                    success: function(res) {
                        const list = (res && res.data && res.data.data) ? res.data.data : (res.data || []);
                        list.forEach(function(s) {
                            $state.append($('<option>').val(s.id).text(s.name)
                                .attr('data-name', s.name)
                                .attr('data-lat', s.latitude || '')
                                .attr('data-lng', s.longitude || ''));
                        });
                        $state.prop('disabled', false);
                    }
                });
            });

            $state.on('change', function() {
                const stateId = $(this).val();
                resetSelect($city, '{{ __('City') }}');
                if (!stateId) return;
                $.ajax({
                    url: '{{ url('api/cities') }}',
                    data: { state_id: stateId, per_page: 1000 },
                    success: function(res) {
                        const list = (res && res.data && res.data.data) ? res.data.data : (res.data || []);
                        list.forEach(function(c) {
                            $city.append($('<option>').val(c.id).text(c.name)
                                .attr('data-name', c.name)
                                .attr('data-lat', c.latitude || '')
                                .attr('data-lng', c.longitude || ''));
                        });
                        $city.prop('disabled', false);
                    }
                });
            });

            $('#manual-location-save').on('click', function() {
                const countryName = $country.find('option:selected').data('name') || '';
                const stateName = $state.find('option:selected').data('name') || '';
                const cityName = $city.find('option:selected').data('name') || '';
                const addressText = ($address.val() || '').trim();

                if (!countryName) {
                    showErrorToast('{{ __('Please select a country') }}');
                    return;
                }
                if (!stateName) {
                    showErrorToast('{{ __('Please select a state') }}');
                    return;
                }
                if (!cityName) {
                    showErrorToast('{{ __('Please select a city') }}');
                    return;
                }

                const $citySel = $city.find('option:selected');
                const $stateSel = $state.find('option:selected');
                const $countrySel = $country.find('option:selected');
                const lat = $citySel.data('lat') || $stateSel.data('lat') || $countrySel.data('lat') || '';
                const lng = $citySel.data('lng') || $stateSel.data('lng') || $countrySel.data('lng') || '';

                const fullAddress = [addressText, cityName, stateName, countryName].filter(Boolean).join(', ');

                $('#country-input').val(countryName);
                $('#state-input').val(stateName);
                $('#city-input').val(cityName);
                $('#address-hidden').val(fullAddress);
                if (lat && lng) {
                    $('#latitude-input').val(lat);
                    $('#longitude-input').val(lng);
                    if (typeof selectLocationFromCoords === 'function') {
                        selectLocationFromCoords(parseFloat(lat), parseFloat(lng));
                    }
                }

                $('#selected-address-display').show();
                $('#selected-address-text').text(fullAddress);
                $('#location-search').val(fullAddress);

                bootstrap.Modal.getInstance(document.getElementById('manualLocationModal')).hide();
            });

            $('#manualLocationModal').on('hidden.bs.modal', function() {
                // keep values; just allow reopen
            });
        });
        @endif

        // Success callback function for advertisement form
        window.handleAdvertisementSuccess = function(response) {
            // Redirect to advertisement index page
            window.location.href = '{{ route('advertisement.index') }}';
        };
    </script>
@endsection
