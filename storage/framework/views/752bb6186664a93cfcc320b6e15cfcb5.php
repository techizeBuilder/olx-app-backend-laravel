
<?php $__env->startSection('title'); ?>
    <?php echo e(__("Verification Fields")); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-title'); ?>
    <div class="page-title">
        <div class="row d-flex align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0"><?php echo $__env->yieldContent('title'); ?></h4>
            </div>
            <div class="col-12 col-md-6 text-end">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('seller-verification-field-create')): ?>
                    <a href="<?php echo e(route('seller-verification.create')); ?>" class="btn btn-primary mb-0">+ <?php echo e(__("Create Verification Field")); ?> </a>
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
                        <table class="stable-borderless table-striped" aria-describedby="mydesc" id="table_list"
                               data-toggle="table" data-url="<?php echo e(route('verification-field.show')); ?>" data-click-to-select="true"
                               data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                               data-search="true" data-search-align="right" data-toolbar="#filters" data-show-columns="true"
                               data-show-refresh="true" data-fixed-columns="true" data-fixed-number="1" data-fixed-right-number="1"
                               data-trim-on-search="false" data-responsive="true" data-sort-name="id" data-sort-order="desc"
                               data-pagination-successively-size="3" data-escape="true"
                               data-show-export="true" data-export-options='{"fileName": "verification-fields-list","ignoreColumn": ["operate"]}' data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']"
                               data-mobile-responsive="true" data-filter-control="true" data-filter-control-container="#filters">
                            <thead class="thead-dark">
                                <tr>
                                    <th scope="col" data-field="id" data-align="center" data-sortable="true"><?php echo e(__('ID')); ?></th>
                                    <th scope="col" data-field="name" data-align="center" data-sortable="true"><?php echo e(__('Name')); ?></th>
                                    <th scope="col" data-field="min_length" data-align="center" data-sortable="true"><?php echo e(__('Min Length')); ?></th>
                                    <th scope="col" data-field="max_length" data-align="center" data-sortable="true"><?php echo e(__('Max Length')); ?></th>
                                    <th scope="col" data-field="values" data-align="center" data-sortable="true" data-formatter="rejectedReasonFormatter"><?php echo e(__('Values')); ?></th>
                                    
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['seller-verification-field-update','seller-verification-field-delete'])): ?>
                                        <th scope="col" data-field="operate" data-align="center" data-sortable="false" data-escape="false" data-events="verificationfeildEvents"><?php echo e(__('Action')); ?></th>
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


<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\OLX\resources\views/seller-verification/verificationfield.blade.php ENDPATH**/ ?>