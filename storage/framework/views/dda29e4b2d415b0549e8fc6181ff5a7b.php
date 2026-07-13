

<?php $__env->startSection('title'); ?>
    <?php echo e(__('States')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-title'); ?>
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4><?php echo $__env->yieldContent('title'); ?></h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <section class="section">
        <div class="row m-3">
            <div class="col-12 text-end">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('state-update')): ?>
                    <a href="<?php echo e(route('states.translation')); ?>" class="btn btn-primary">
                        <i class="fa fa-language me-2"></i> <?php echo e(__('Translate States')); ?>

                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div id="filters">
                            <div class="row">
                                <div class="col-12 col-md-12">
                                    <label for="filter_country"><?php echo e(__("Country")); ?></label>
                                    <select class="form-control bootstrap-table-filter-control-country_name" id="filter_country">
                                        <option value=""><?php echo e(__("All")); ?></option>
                                        <?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($country->id); ?>"><?php echo e($country->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <table class="table-borderless table-striped" aria-describedby="mydesc" id="table_list"
                                       data-toggle="table" data-url="<?php echo e(route('states.show',1)); ?>" data-click-to-select="true"
                                       data-side-pagination="server" data-pagination="true"
                                       data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                                       data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                                       data-fixed-number="1" data-fixed-right-number="1" data-trim-on-search="false"
                                       data-responsive="true" data-sort-name="id" data-sort-order="desc"
                                       data-pagination-successively-size="3" data-table="states" data-status-column="deleted_at"
                                       data-escape="true"
                                       data-show-export="true" data-export-options='{"fileName": "state-list","ignoreColumn": ["operate"]}' data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']"
                                       data-mobile-responsive="true" data-filter-control="true" data-filter-control-container="#filters" data-toolbar="#filters">
                                    <thead class="thead-dark">
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true"><?php echo e(__('ID')); ?></th>
                                        <th scope="col" data-field="name" data-sortable="true"><?php echo e(__('Name')); ?></th>
                                        <th scope="col" data-field="country_name" data-sortable="true" data-filter-name="country_id" data-filter-control="select" data-filter-data=""><?php echo e(__('Country')); ?></th>
                                        <th scope="col" data-field="country.emoji"><?php echo e(__('Flag')); ?></th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\OLX\resources\views/places/state.blade.php ENDPATH**/ ?>