@extends('layouts.main')

@section('title')
    {{ __('Create Banner Ads') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="page-title">{{ __('Create Banner Ads') }}</h3>
            <a href="{{ route('banner-ads.index') }}" class="btn btn-primary">{{ __('Back') }}</a>
        </div>

        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">

                        {{-- Wizard steps --}}
                        <ul class="nav nav-pills justify-content-between mb-4" id="banner-steps">
                            <li class="nav-item"><span class="nav-link active" data-step="1">1. {{ __('Platform and Select Page') }}</span></li>
                            <li class="nav-item"><span class="nav-link" data-step="2">2. {{ __('Banner Layout') }}</span></li>
                            <li class="nav-item"><span class="nav-link" data-step="3">3. {{ __('Upload Banner') }}</span></li>
                            <li class="nav-item"><span class="nav-link" data-step="4">4. {{ __('Banner Placement') }}</span></li>
                        </ul>

                        <form id="banner-form" class="create-form-without-reset"
                              action="{{ route('banner-ads.store') }}" method="POST"
                              enctype="multipart/form-data" data-success-function="bannerSuccess">
                            @csrf

                            {{-- ---------- STEP 1 : Platform + Page ---------- --}}
                            <div class="banner-step" data-step="1">
                                <label class="form-label mandatory">{{ __('Select Platform') }}</label>
                                <div class="row mb-4">
                                    @foreach (['website' => __('Website'), 'app' => __('App')] as $value => $label)
                                        <div class="col-md-6 mb-2">
                                            <label class="choice-card w-100 border rounded p-3 d-flex justify-content-between align-items-center">
                                                <span>{{ $label }}</span>
                                                <input type="radio" name="platform" value="{{ $value }}" class="form-check-input m-0">
                                            </label>
                                        </div>
                                    @endforeach
                                </div>

                                <div id="page-block" class="d-none">
                                    <label class="form-label mandatory">{{ __('Select Page') }}</label>
                                    <div class="row mb-3">
                                        @foreach (['home' => __('Home Page'), 'details' => __('Details Page'), 'listing' => __('Listing Page')] as $value => $label)
                                            <div class="col-md-4 mb-2">
                                                <label class="choice-card w-100 border rounded p-3 d-flex justify-content-between align-items-center">
                                                    <span>{{ $label }}</span>
                                                    <input type="radio" name="page" value="{{ $value }}" class="form-check-input m-0">
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- ---------- STEP 2 : Layout ---------- --}}
                            <div class="banner-step d-none" data-step="2">
                                <label class="form-label mandatory">{{ __('Banner Layout') }}</label>
                                <div class="row mb-3">
                                    @foreach (['single' => __('Single Banner'), 'dual' => __('Dual Banner')] as $value => $label)
                                        <div class="col-md-6 mb-2">
                                            <label class="choice-card w-100 border rounded p-3 d-flex justify-content-between align-items-center">
                                                <span>{{ $label }}</span>
                                                <input type="radio" name="layout" value="{{ $value }}" class="form-check-input m-0">
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- ---------- STEP 3 : Upload ---------- --}}
                            <div class="banner-step d-none" data-step="3">
                                <div class="bg-light rounded p-3 mb-4 d-flex flex-wrap gap-4" id="banner-summary"></div>

                                @for ($i = 0; $i < 2; $i++)
                                    <div class="card mb-3 banner-slot" data-index="{{ $i }}" @if ($i === 1) style="display:none" @endif>
                                        <div class="card-body">
                                            <h6 class="mb-3">{{ __('Banner') }} {{ $i + 1 }}</h6>

                                            <div class="mb-3">
                                                <label class="form-label mandatory">{{ __('Banner Image') }}</label>
                                                <input type="file" name="banners[{{ $i }}][image]" accept=".jpg,.jpeg,.png"
                                                       class="form-control banner-image-input">
                                                <div class="d-flex justify-content-between mt-1">
                                                    <small class="text-muted">{{ __('Supported Format .JPG, .PNG') }}</small>
                                                    <small class="text-muted">{{ __('Maximum Size : 8 MB | Recommended Size : 741 x 220 px') }}</small>
                                                </div>
                                                <img src="" alt="" class="banner-preview mt-2 rounded d-none"
                                                     style="max-width:320px;max-height:110px;object-fit:cover;">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label mandatory">{{ __('Banner Ad Type') }}</label>
                                                <select name="banners[{{ $i }}][ad_type]" class="form-control banner-ad-type">
                                                    <option value="only_banner">{{ __('Only Banner') }}</option>
                                                    <option value="category">{{ __('Category') }}</option>
                                                    <option value="advertisement">{{ __('Advertisement') }}</option>
                                                    <option value="external_link">{{ __('External Link') }}</option>
                                                </select>
                                            </div>

                                            <div class="mb-3 target-field target-category d-none">
                                                <label class="form-label mandatory">{{ __('Select Category') }}</label>
                                                <select name="banners[{{ $i }}][category_id]" class="form-control">
                                                    <option value="">{{ __('Select a Category') }}</option>
                                                    @include('category.dropdowntree', ['categories' => $categories])
                                                </select>
                                            </div>

                                            <div class="mb-3 target-field target-advertisement d-none">
                                                <label class="form-label mandatory">{{ __('Select Advertisement') }}</label>
                                                <select name="banners[{{ $i }}][item_id]" class="form-control">
                                                    <option value="">{{ __('Select Advertisement') }}</option>
                                                    @foreach ($items as $item)
                                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-3 target-field target-external_link d-none">
                                                <label class="form-label mandatory">{{ __('External Link') }}</label>
                                                <input type="url" name="banners[{{ $i }}][external_link]"
                                                       class="form-control" placeholder="https://example.com">
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            {{-- ---------- STEP 4 : Placement ---------- --}}
                            <div class="banner-step d-none" data-step="4">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6>{{ __('Drag Section') }}</h6>
                                                <p class="text-muted small">{{ __('Drag the banner to reorder its position on the page.') }}</p>
                                                <ul class="list-unstyled mb-0" id="placement-list">
                                                    @foreach ($sections as $section)
                                                        @php $isBanner = $section === 'Banner Ad (New)'; @endphp
                                                        <li class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center
                                                                   {{ $isBanner ? 'banner-placeholder border-primary' : 'text-muted bg-light' }}"
                                                            @if ($isBanner) data-banner="1" style="cursor:grab" @endif>
                                                            <span>{{ __($section) }}</span>
                                                            <i class="fas {{ $isBanner ? 'fa-arrows-alt' : 'fa-lock' }}"></i>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                                <input type="hidden" name="sequence" id="banner-sequence" value="1">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="alert alert-info">
                                            {{ __('The banner will appear at position') }}
                                            <strong id="placement-position">1</strong>
                                            {{ __('on the selected page.') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ---------- Nav buttons ---------- --}}
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-secondary d-none" id="btn-prev">{{ __('Back') }}</button>
                                <button type="button" class="btn btn-primary" id="btn-next" disabled>{{ __('Continue') }}</button>
                                <button type="submit" class="btn btn-success d-none" id="btn-submit">{{ __('Save') }}</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @include('banner-ads.wizard-script')
    <script>
        function bannerSuccess() {
            setTimeout(function () {
                window.location.href = "{{ route('banner-ads.index') }}";
            }, 1000);
        }
    </script>
@endsection
