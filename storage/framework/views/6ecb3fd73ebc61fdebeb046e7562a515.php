<?php $__env->startSection('title'); ?>
    <?php echo e(__('Countries')); ?>

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
       <div class="buttons d-flex justify-content-end">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('country-create')): ?>
            <a class="btn btn-primary" href="#" data-bs-toggle="modal" data-bs-target="#countryModal">
                + <?php echo e(__("Import Countries")); ?>

            </a>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('country-update')): ?>
            <a class="btn btn-primary ms-2" href="<?php echo e(route('countries.translation')); ?>">
                 <i class="fa fa-language me-2"></i> <?php echo e(__("Translate Countries")); ?></a>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <table class="table-borderless table-striped" aria-describedby="mydesc" id="table_list"
                               data-toggle="table" data-url="<?php echo e(route('countries.show',1)); ?>" data-click-to-select="true"
                               data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                               data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                               data-fixed-number="1" data-fixed-right-number="1" data-trim-on-search="false"
                               data-responsive="true" data-sort-name="id" data-sort-order="desc"
                               data-pagination-successively-size="3" data-table="countries" data-status-column="deleted_at"
                               data-escape="true"
                               data-show-export="true" data-export-options='{"fileName": "country-list","ignoreColumn": ["operate"]}' data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']"
                               data-mobile-responsive="true" data-filter-control="true" data-filter-control-container="#filters" data-toolbar="#filters">
                            <thead class="thead-dark">
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true"><?php echo e(__('ID')); ?></th>
                                <th scope="col" data-field="name" data-sortable="true"><?php echo e(__('Name')); ?></th>
                                <th scope="col" data-field="emoji"><?php echo e(__('Flag')); ?></th>
                                <th scope="col" data-field="operate" data-escape="false"><?php echo e(__('Action')); ?></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="countryModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel1"><?php echo e(__('Import Country Data')); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form class="create-form" action="<?php echo e(route('countries.import')); ?>" method="POST" data-success-function="successFunction">
                            <?php echo csrf_field(); ?>

                            <div class="row">
                                <div class="col-12 mb-3">
                                    <input type="text" id="countrySearchInput" class="form-control" placeholder="<?php echo e(__('Search countries...')); ?>">
                                </div>
                                <div class="col-12 mb-2">
                                    <input type="checkbox" id="selectAllCountries" class="form-check-input">
                                    <label for="selectAllCountries" class="form-label"><?php echo e(__('Select All')); ?></label>
                                </div>

                                <?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="col-md-3">
                                        <input type="checkbox" id="<?php echo e($country['id']); ?>" name="countries[]" value="<?php echo e($country['id']); ?>" <?php echo e($country['is_already_exists'] ? "checked disabled" : ""); ?> class="form-check-input">
                                        <label for="<?php echo e($country['id']); ?>" class="form-label"><?php echo e($country['name'].' '.$country['emoji']); ?></label>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <div class="text-end">
                                <input type="submit" value="<?php echo e(__("Save")); ?>" class="btn btn-primary mt-3">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
    </section>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('js'); ?>
    <script>
        function successFunction() {
            $('#countryModal').modal('hide');
        }
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Admin Panel 2.12.0\Eclassify Version 2.12.0 Fresh Installation\resources\views/places/country.blade.php ENDPATH**/ ?>