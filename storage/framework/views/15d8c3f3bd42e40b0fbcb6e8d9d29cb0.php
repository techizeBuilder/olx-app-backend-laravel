<?php $__env->startSection('title'); ?>
    <?php echo e(__('Create Currency')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-title'); ?>
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4><?php echo $__env->yieldContent('title'); ?></h4>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <section class="section">
        <div class="buttons">
            <a class="btn btn-primary" href="<?php echo e(route('currency.index')); ?>">
                < <?php echo e(__('Back to Currencies')); ?> </a>
        </div>
        <div class="row">
            <form action="<?php echo e(route('currency.store')); ?>" class="create-form" data-parsley-validate method="POST"
                data-success-function="afterCustomFieldCreationSuccess" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="card">
                    <div class="card-header"><?php echo e(__('Create Currency')); ?></div>
                    <div class="card-body mt-3">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3 mandatory">
                                    <label for="iso_code"><?php echo e(__('ISO Code')); ?></label>
                                    <input type="text" name="iso_code" id="iso_code" class="form-control"
                                        placeholder="e.g. USD, INR" maxlength="3" required pattern="[A-Z]{3}"
                                        oninput="this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '')">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3 mandatory">
                                    <label for="name"><?php echo e(__('Name')); ?></label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        data-parsley-required="true" placeholder="Enter the Currency Name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3 mandatory">
                                    <label for="code"><?php echo e(__('Symbol')); ?></label>
                                    <input type="text" name="symbol" id="code" class="form-control"
                                        data-parsley-required="true" placeholder="Currency Symbol" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3 mandatory">
                                    <label for="country" class="form-label"><?php echo e(__('Country')); ?></label>
                                    <select class="form-control" id="country_item" name="country_id"
                                        data-parsley-required="true">
                                        <option value="">--Select Country--</option>
                                        <?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($country->id); ?>">
                                                <?php echo e($country->name); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="symbol_position"
                                        class="form-label"><?php echo e(__('Currency Symbol Position')); ?></label>
                                    <div class="mt-2 d-flex align-items-center">
                                        <div class="form-check me-3">
                                            <input type="radio" id="currency_symbol_left" name="symbol_position"
                                                value="left" class="form-check-input" checked>
                                            <label for="currency_symbol_left"
                                                class="form-check-label"><?php echo e(__('Left')); ?></label>
                                        </div>
                                        <div class="form-check">
                                            <input type="radio" id="currency_symbol_right" name="symbol_position"
                                                value="right" class="form-check-input">
                                            <label for="currency_symbol_right"
                                                class="form-check-label"><?php echo e(__('Right')); ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="decimal_places" class="form-label"><?php echo e(__('Decimal Places')); ?></label>
                                    <input type="number" name="decimal_places" id="decimal_places" class="form-control"
                                        min="0" max="6" placeholder="Ex. 2">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="thousand_separator"
                                        class="form-label"><?php echo e(__('Thousand Separator')); ?></label>
                                    <input type="text" name="thousand_separator" id="thousand_separator"
                                        class="form-control" maxlength="1" placeholder="Ex. ,">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="decimal_separator"
                                        class="form-label"><?php echo e(__('Decimal Separator')); ?></label>
                                    <input type="text" name="decimal_separator" id="decimal_separator"
                                        class="form-control" maxlength="1" placeholder="Ex. .">
                                </div>
                            </div>
                            
                        </div>

                    </div>
                </div>
                <div class="col-md-12 text-end">
                    <input type="submit" class="btn btn-primary" value="<?php echo e(__('Save and Back')); ?>">
                </div>
            </form>
        </div>
    </section>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
    <script>
        function afterCustomFieldCreationSuccess() {
        setTimeout(function () {
            window.location.href = "<?php echo e(route('currency.index')); ?>";
        }, 1000)
     }

    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Admin Panel 2.12.0\Eclassify Version 2.12.0 Fresh Installation\resources\views/currency/create.blade.php ENDPATH**/ ?>