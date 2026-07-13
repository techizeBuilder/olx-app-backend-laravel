@extends('layouts.main')

@section('title')
    {{ __('Edit Banner Ad') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="page-title">{{ __('Edit Banner Ad') }}</h3>
            <a href="{{ route('banner-ads.index') }}" class="btn btn-primary">{{ __('Back') }}</a>
        </div>

        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">

                        <form id="banner-edit-form" class="edit-form" action="{{ route('banner-ads.update', $banner->id) }}"
                              method="POST" enctype="multipart/form-data" data-success-function="bannerSuccess">
                            @csrf
                            @method('PUT')

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label mandatory">{{ __('Platform') }}</label>
                                    <select name="platform" class="form-control">
                                        <option value="website" @selected($banner->platform === 'website')>{{ __('Website') }}</option>
                                        <option value="app" @selected($banner->platform === 'app')>{{ __('App') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mandatory">{{ __('Page') }}</label>
                                    <select name="page" class="form-control">
                                        <option value="home" @selected($banner->page === 'home')>{{ __('Home Page') }}</option>
                                        <option value="details" @selected($banner->page === 'details')>{{ __('Details Page') }}</option>
                                        <option value="listing" @selected($banner->page === 'listing')>{{ __('Listing Page') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mandatory">{{ __('Banner Layout') }}</label>
                                    <select name="layout" class="form-control" id="edit-layout">
                                        <option value="single" @selected($banner->layout === 'single')>{{ __('Single Banner') }}</option>
                                        <option value="dual" @selected($banner->layout === 'dual')>{{ __('Dual Banner') }}</option>
                                    </select>
                                </div>
                            </div>

                            @for ($i = 0; $i < 2; $i++)
                                @php $item = $banner->bannerItems->firstWhere('position', $i + 1); @endphp
                                <div class="card mb-3 banner-slot" data-index="{{ $i }}"
                                     @if ($i === 1 && $banner->layout !== 'dual') style="display:none" @endif>
                                    <div class="card-body">
                                        <h6 class="mb-3">{{ __('Banner') }} {{ $i + 1 }}</h6>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Banner Image') }}</label>
                                            @if ($item)
                                                <div class="mb-2">
                                                    <img src="{{ $item->image }}" alt="banner" class="rounded"
                                                         style="max-width:320px;max-height:110px;object-fit:cover;"
                                                         onerror="onErrorImage(event)">
                                                </div>
                                            @endif
                                            <input type="file" name="banners[{{ $i }}][image]" accept=".jpg,.jpeg,.png"
                                                   class="form-control">
                                            <small class="text-muted">
                                                {{ __('Leave empty to keep the current image. Max 8 MB, recommended 741 x 220 px.') }}
                                            </small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label mandatory">{{ __('Banner Ad Type') }}</label>
                                            <select name="banners[{{ $i }}][ad_type]" class="form-control banner-ad-type">
                                                @foreach (['only_banner' => __('Only Banner'), 'category' => __('Category'), 'advertisement' => __('Advertisement'), 'external_link' => __('External Link')] as $value => $text)
                                                    <option value="{{ $value }}" @selected($item?->ad_type === $value)>{{ $text }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3 target-field target-category @if ($item?->ad_type !== 'category') d-none @endif">
                                            <label class="form-label mandatory">{{ __('Select Category') }}</label>
                                            <select name="banners[{{ $i }}][category_id]" class="form-control">
                                                <option value="">{{ __('Select a Category') }}</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}" @selected($item?->category_id === $category->id)>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3 target-field target-advertisement @if ($item?->ad_type !== 'advertisement') d-none @endif">
                                            <label class="form-label mandatory">{{ __('Select Advertisement') }}</label>
                                            <select name="banners[{{ $i }}][item_id]" class="form-control">
                                                <option value="">{{ __('Select Advertisement') }}</option>
                                                @foreach ($items as $ad)
                                                    <option value="{{ $ad->id }}" @selected($item?->item_id === $ad->id)>{{ $ad->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3 target-field target-external_link @if ($item?->ad_type !== 'external_link') d-none @endif">
                                            <label class="form-label mandatory">{{ __('External Link') }}</label>
                                            <input type="url" name="banners[{{ $i }}][external_link]" class="form-control"
                                                   value="{{ $item?->external_link }}" placeholder="https://example.com">
                                        </div>
                                    </div>
                                </div>
                            @endfor

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Position on page') }}</label>
                                    <input type="number" name="sequence" class="form-control" min="0"
                                           value="{{ $banner->sequence }}">
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        // Dual layout shows the second banner slot.
        $('#edit-layout').on('change', function () {
            $('.banner-slot[data-index=1]').toggle($(this).val() === 'dual');
        });

        // Ad type reveals its matching target field.
        $(document).on('change', '.banner-ad-type', function () {
            const $slot = $(this).closest('.banner-slot');
            const type = $(this).val();

            $slot.find('.target-field').addClass('d-none');
            if (type !== 'only_banner') {
                $slot.find('.target-' + type).removeClass('d-none');
            }
        });

        function bannerSuccess() {
            setTimeout(function () {
                window.location.href = "{{ route('banner-ads.index') }}";
            }, 1000);
        }
    </script>
@endsection
