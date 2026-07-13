@extends('layouts.main')

@section('title')
    {{ __('Banner Ads') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="page-title">{{ __('Banner Ads') }}</h3>
            @can('banner-ad-create')
                <a href="{{ route('banner-ads.create') }}" class="btn btn-primary">{{ __('Create Banner Ad') }}</a>
            @endcan
        </div>

        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">

                        <div class="row" id="filters">
                            <div class="col-lg-3 col-sm-6">
                                <label for="filter_platform" class="form-label">{{ __('Platform') }}</label>
                                <select class="form-control" id="filter_platform">
                                    <option value="">{{ __('All') }}</option>
                                    <option value="website">{{ __('Website') }}</option>
                                    <option value="app">{{ __('App') }}</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label for="filter_page" class="form-label">{{ __('Page') }}</label>
                                <select class="form-control" id="filter_page">
                                    <option value="">{{ __('All') }}</option>
                                    <option value="home">{{ __('Homepage') }}</option>
                                    <option value="details">{{ __('Ads Details Page') }}</option>
                                    <option value="listing">{{ __('Listing Page') }}</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label for="filter_layout" class="form-label">{{ __('Layout') }}</label>
                                <select class="form-control" id="filter_layout">
                                    <option value="">{{ __('All') }}</option>
                                    <option value="single">{{ __('Single') }}</option>
                                    <option value="dual">{{ __('Dual') }}</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label for="filter_ad_type" class="form-label">{{ __('Ad Type') }}</label>
                                <select class="form-control" id="filter_ad_type">
                                    <option value="">{{ __('All') }}</option>
                                    <option value="only_banner">{{ __('Only Banner') }}</option>
                                    <option value="category">{{ __('Category') }}</option>
                                    <option value="advertisement">{{ __('Advertisement') }}</option>
                                    <option value="external_link">{{ __('External Link') }}</option>
                                </select>
                            </div>
                            <div class="col-12 mt-2 text-end">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-clear-banner-filters">
                                    {{ __('Clear Filters') }}
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table table-borderless table-striped" aria-describedby="banner-ads"
                                   id="table_list" data-toggle="table"
                                   data-url="{{ route('banner-ads.show') }}"
                                   data-side-pagination="server" data-pagination="true"
                                   data-page-list="[5, 10, 20, 50, 100]" data-search="true"
                                   data-show-columns="true" data-show-refresh="true"
                                   data-query-params="bannerQueryParams"
                                   data-sort-name="id" data-sort-order="desc"
                                   data-escape="false" data-table="banners"
                                   data-show-export="true"
                                   data-export-options='{"fileName": "banner-ads-list","ignoreColumn": ["operate"]}'
                                   data-export-types="['pdf','json','xml','csv','txt','sql','doc','excel']">
                                <thead class="thead-dark">
                                    <tr>
                                        <th scope="col" data-field="images" data-align="center" data-sortable="false"
                                            data-formatter="bannerImagesFormatter">{{ __('Banner Image') }}</th>
                                        <th scope="col" data-field="platform_label" data-sortable="true"
                                            data-sort-name="platform">{{ __('Platform') }}</th>
                                        <th scope="col" data-field="page_label" data-sortable="true"
                                            data-sort-name="page">{{ __('Page') }}</th>
                                        <th scope="col" data-field="layout_label" data-sortable="true"
                                            data-sort-name="layout">{{ __('Layout') }}</th>
                                        <th scope="col" data-field="ad_type_label" data-sortable="false">{{ __('Ad Type') }}</th>
                                        <th scope="col" data-field="operate" data-escape="false" data-align="center"
                                            data-sortable="false">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        // Show every image belonging to the banner group (1 for single, 2 for dual).
        function bannerImagesFormatter(value) {
            if (!value || !value.length) {
                return '-';
            }
            return value.map(function (src) {
                return '<img src="' + src + '" alt="banner" class="me-2 rounded" ' +
                    'style="width:130px;height:44px;object-fit:cover;" onerror="onErrorImage(event)">';
            }).join('');
        }

        function bannerQueryParams(params) {
            params.platform = $('#filter_platform').val() || '';
            params.page = $('#filter_page').val() || '';
            params.layout = $('#filter_layout').val() || '';
            params.ad_type = $('#filter_ad_type').val() || '';
            return params;
        }

        $('#filter_platform, #filter_page, #filter_layout, #filter_ad_type').on('change', function () {
            $('#table_list').bootstrapTable('refresh');
        });

        $('#btn-clear-banner-filters').on('click', function () {
            $('#filter_platform, #filter_page, #filter_layout, #filter_ad_type').val('');
            $('#table_list').bootstrapTable('refresh');
        });
    </script>
@endsection
