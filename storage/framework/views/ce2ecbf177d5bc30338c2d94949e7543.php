<?php $__env->startSection('title'); ?>
    <?php echo e(__('Banner Ads')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="content-wrapper">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="page-title"><?php echo e(__('Banner Ads')); ?></h3>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('banner-ad-create')): ?>
                <a href="<?php echo e(route('banner-ads.create')); ?>" class="btn btn-primary"><?php echo e(__('Create Banner Ad')); ?></a>
            <?php endif; ?>
        </div>

        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">

                        <div class="row" id="filters">
                            <div class="col-lg-3 col-sm-6">
                                <label for="filter_platform" class="form-label"><?php echo e(__('Platform')); ?></label>
                                <select class="form-control" id="filter_platform">
                                    <option value=""><?php echo e(__('All')); ?></option>
                                    <option value="website"><?php echo e(__('Website')); ?></option>
                                    <option value="app"><?php echo e(__('App')); ?></option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label for="filter_page" class="form-label"><?php echo e(__('Page')); ?></label>
                                <select class="form-control" id="filter_page">
                                    <option value=""><?php echo e(__('All')); ?></option>
                                    <option value="home"><?php echo e(__('Homepage')); ?></option>
                                    <option value="details"><?php echo e(__('Ads Details Page')); ?></option>
                                    <option value="listing"><?php echo e(__('Listing Page')); ?></option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label for="filter_layout" class="form-label"><?php echo e(__('Layout')); ?></label>
                                <select class="form-control" id="filter_layout">
                                    <option value=""><?php echo e(__('All')); ?></option>
                                    <option value="single"><?php echo e(__('Single')); ?></option>
                                    <option value="dual"><?php echo e(__('Dual')); ?></option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label for="filter_ad_type" class="form-label"><?php echo e(__('Ad Type')); ?></label>
                                <select class="form-control" id="filter_ad_type">
                                    <option value=""><?php echo e(__('All')); ?></option>
                                    <option value="only_banner"><?php echo e(__('Only Banner')); ?></option>
                                    <option value="category"><?php echo e(__('Category')); ?></option>
                                    <option value="advertisement"><?php echo e(__('Advertisement')); ?></option>
                                    <option value="external_link"><?php echo e(__('External Link')); ?></option>
                                </select>
                            </div>
                            <div class="col-12 mt-2 text-end">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-clear-banner-filters">
                                    <?php echo e(__('Clear Filters')); ?>

                                </button>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table table-borderless table-striped" aria-describedby="banner-ads"
                                   id="table_list" data-toggle="table"
                                   data-url="<?php echo e(route('banner-ads.show')); ?>"
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
                                            data-formatter="bannerImagesFormatter"><?php echo e(__('Banner Image')); ?></th>
                                        <th scope="col" data-field="platform_label" data-sortable="true"
                                            data-sort-name="platform"><?php echo e(__('Platform')); ?></th>
                                        <th scope="col" data-field="page_label" data-sortable="true"
                                            data-sort-name="page"><?php echo e(__('Page')); ?></th>
                                        <th scope="col" data-field="layout_label" data-sortable="true"
                                            data-sort-name="layout"><?php echo e(__('Layout')); ?></th>
                                        <th scope="col" data-field="ad_type_label" data-sortable="false"><?php echo e(__('Ad Type')); ?></th>
                                        <th scope="col" data-field="operate" data-escape="false" data-align="center"
                                            data-sortable="false"><?php echo e(__('Action')); ?></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script>
        // Backend already sends the full image URL — use it directly.
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\OLX\resources\views/banner-ads/index.blade.php ENDPATH**/ ?>