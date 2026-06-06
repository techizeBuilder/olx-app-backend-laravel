<?php $__env->startSection('title'); ?>
    <?php echo e(__('Currencies')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-title'); ?>
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h4><?php echo $__env->yieldContent('title'); ?></h4>
            </div>
            <div class="col-12 col-md-6 d-flex justify-content-end">
                <a class="btn btn-primary" href="<?php echo e(route('currency.create')); ?>"><?php echo e(__('Create Currency')); ?></a>
                
            </div>
        </div>
    <?php $__env->stopSection(); ?>

    <?php $__env->startSection('content'); ?>
        <section class="section">
            

            
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <table class="table-borderless table-striped" aria-describedby="mydesc" id="table_list"
                                data-toggle="table" data-url="<?php echo e(route('currency.show', 1)); ?>" data-click-to-select="true"
                                data-side-pagination="server" data-pagination="true"
                                data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true"
                                data-show-refresh="true" data-fixed-columns="true" data-fixed-number="1"
                                data-fixed-right-number="1" data-trim-on-search="false" data-responsive="true"
                                data-sort-name="id" data-sort-order="desc" data-pagination-successively-size="3"
                                data-table="currencies" data-status-column="deleted_at" data-escape="true"
                                data-show-export="true"
                                data-export-options='{"fileName": "currency-list","ignoreColumn": ["operate"]}'
                                data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']"
                                data-mobile-responsive="true" data-filter-control="true"
                                data-filter-control-container="#filters" data-toolbar="#filters">
                                <thead class="thead-dark">
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true"><?php echo e(__('ID')); ?></th>
                                        <th scope="col" data-field="iso_code" data-sortable="true"><?php echo e(__('ISO Code')); ?>

                                        </th>
                                        <th scope="col" data-field="name" data-sortable="true"><?php echo e(__('Name')); ?></th>
                                        <th scope="col" data-field="symbol" data-sortable="true"><?php echo e(__('Symbol')); ?></th>
                                        <th scope="col" data-field="symbol_position" data-sortable="true">
                                            <?php echo e(__('Symbol Position')); ?></th>
                                        <th scope="col" data-field="country.name" data-sort-name="country_name"
                                            data-sortable="true"><?php echo e(__('Country')); ?>

                                        </th>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check(['currency-update', 'currency-delete'])): ?>
                                            <th scope="col" data-escape="false" data-field="operate"><?php echo e(__('Action')); ?>

                                            </th>
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

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Admin Panel 2.12.0\Eclassify Version 2.12.0 Fresh Installation\resources\views/currency/index.blade.php ENDPATH**/ ?>