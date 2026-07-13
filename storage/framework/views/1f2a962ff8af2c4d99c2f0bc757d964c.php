

<?php $__env->startSection('title'); ?>
    <?php echo e(__('Subscription Packages')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-title'); ?>
    <div class="page-title">
        <div class="row d-flex align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0"><?php echo $__env->yieldContent('title'); ?></h4>
            </div>
            <div class="col-12 col-md-6 text-end">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('advertisement-listing-package-create')): ?>
                    <a class="btn btn-primary me-2"
                        href="<?php echo e(route('package.create')); ?>"><?php echo e(__('Create Subscription Package')); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <section class="section">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div id="filters">
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <label for="type"><?php echo e(__('Package Type')); ?></label>
                                    <select name="type" class="form-control bootstrap-table-filter-control-type"
                                        aria-label="type" id="type">
                                        <option value=""><?php echo e(__('All')); ?></option>
                                        <option value="item_listing"><?php echo e(__('Item Listing (Ads)')); ?></option>
                                        <option value="advertisement"><?php echo e(__('Advertisement (Featured Ads)')); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <table class="stable-borderless table-striped" aria-describedby="mydesc" id="table_list"
                            data-toggle="table" data-url="<?php echo e(route('package.show', 1)); ?>" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                            data-search="true" data-search-align="right" data-toolbar="#filters" data-show-columns="true"
                            data-show-refresh="true" data-fixed-columns="true" data-fixed-number="1"
                            data-fixed-right-number="1" data-trim-on-search="false" data-responsive="true"
                            data-sort-name="id" data-sort-order="desc" data-pagination-successively-size="3"
                            data-escape="true" data-show-export="true"
                            data-export-options='{"fileName": "package-list","ignoreColumn": ["operate"]}'
                            data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']"
                            data-mobile-responsive="true" data-filter-control="true"
                            data-filter-control-container="#filters"
                            data-table="packages">
                            <thead class="thead-dark">
                                <tr>
                                    <th scope="col" data-field="id" data-align="center" data-sortable="true">
                                        <?php echo e(__('ID')); ?></th>
                                    <th scope="col" data-field="icon" data-align="center"
                                        data-formatter="imageFormatter"><?php echo e(__('Image')); ?></th>
                                    <th scope="col" data-field="name" data-align="center" data-escape="true">
                                        <?php echo e(__('Name')); ?></th>
                                    <th scope="col" data-field="type" data-align="center" data-filter-name="type"
                                        data-filter-control="select" data-filter-data=""
                                        data-formatter="packageTypeFormatter"><?php echo e(__('Type')); ?></th>
                                    <th scope="col" data-field="category_names" data-align="center"
                                        data-formatter="categoryNamesFormatter"><?php echo e(__('Categories')); ?></th>
                                    <th scope="col" data-field="price" data-align="center" data-sortable="true">
                                        <?php echo e(__('Price')); ?></th>
                                    <th scope="col" data-field="discount_in_percentage" data-align="center"
                                        data-sortable="true"><?php echo e(__('Discount (%)')); ?></th>
                                    <th scope="col" data-field="final_price" data-align="center" data-sortable="true">
                                        <?php echo e(__('Final Price')); ?></th>
                                    <th scope="col" data-field="duration" data-align="center" data-sortable="true">
                                        <?php echo e(__('Package Duration')); ?></th>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['advertisement-listing-package-update', 'featured-advertisement-package-update'])): ?>
                                        <th scope="col" data-field="status" data-align="center" data-sortable="true"
                                            data-formatter="statusSwitchFormatter" data-escape="false">
                                            <?php echo e(__('Status')); ?></th>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['advertisement-listing-package-update', 'advertisement-listing-package-delete', 'featured-advertisement-package-update', 'featured-advertisement-package-delete'])): ?>
                                        <th scope="col" data-field="operate" data-align="center" data-escape="false" data-sortable="false">
                                            <?php echo e(__('Action')); ?></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\OLX\resources\views/packages/index.blade.php ENDPATH**/ ?>